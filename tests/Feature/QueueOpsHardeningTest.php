<?php

use App\Enums\QueueTicketStatus;
use App\Exceptions\Queue\CounterClaimedException;
use App\Exceptions\Queue\InvalidQueueTransitionException;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueManager;
use App\Support\QueueTransitionValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('claims a counter for an operator and blocks other operators', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $counter = Counter::factory()->create();

    $manager = app(QueueManager::class);
    $manager->claimCounter($counter, $first);

    expect($counter->fresh()->claimed_by_user_id)->toBe($first->id);

    expect(fn () => $manager->claimCounter($counter->fresh(), $second))
        ->toThrow(CounterClaimedException::class);
});

it('allows admin to force-claim a counter', function () {
    $operator = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $counter = Counter::factory()->create();

    $manager = app(QueueManager::class);
    $manager->claimCounter($counter, $operator);
    $manager->claimCounter($counter->fresh(), $admin, force: true);

    expect($counter->fresh()->claimed_by_user_id)->toBe($admin->id);
});

it('releases previous claim when operator claims another counter', function () {
    $operator = User::factory()->create();
    $first = Counter::factory()->create(['code' => 'L1', 'name' => 'Loket 1']);
    $second = Counter::factory()->create(['code' => 'L2', 'name' => 'Loket 2']);

    $manager = app(QueueManager::class);
    $manager->claimCounter($first, $operator);
    $manager->claimCounter($second, $operator);

    expect($first->fresh()->claimed_by_user_id)->toBeNull()
        ->and($second->fresh()->claimed_by_user_id)->toBe($operator->id);
});

it('serializes counter claims per operator by locking the user row', function () {
    $operator = User::factory()->create();
    $counter = Counter::factory()->create();

    app(QueueManager::class)->claimCounter($counter, $operator);

    $source = file_get_contents(app_path('Actions/Queue/ClaimCounter.php'));

    expect($counter->fresh()->claimed_by_user_id)->toBe($operator->id)
        ->and($source)->toContain('User::query()')
        ->and($source)->toContain('lockForUpdate()');
});

it('keeps the operator workflow focused without a transfer action', function () {
    $operator = User::factory()->create();
    $service = Service::factory()->create(['code' => 'T']);
    $counter = Counter::factory()->create(['code' => 'L1', 'name' => 'Loket 1']);
    $counter->services()->attach($service);

    Livewire::actingAs($operator)
        ->test('operator.counter-panel')
        ->assertDontSee('Transfer ke loket lain');
});

it('keeps announcement audio exclusive to the display television', function () {
    $operator = User::factory()->create();
    $service = Service::factory()->create(['code' => 'V', 'name' => 'Vaksinasi']);
    $counter = Counter::factory()->create(['code' => 'L1', 'name' => 'Loket 1']);
    $counter->services()->attach($service);

    $ticket = app(QueueManager::class)->issueFromKiosk($service)->ticket;

    $component = Livewire::actingAs($operator)
        ->test('operator.counter-panel')
        ->call('callNext');

    $component
        ->assertNotDispatched('play-announcement')
        ->assertDispatched('queue-updated')
        ->assertSee("Memanggil {$ticket->ticket_number}");

    $blade = file_get_contents(resource_path('views/components/operator/⚡counter-panel.blade.php'));

    expect($blade)
        ->not->toContain("dispatch('play-announcement'")
        ->toContain('wire:loading.attr="disabled"')
        ->toContain('wire:target="recall({{ $currentTicket->id }})"');
});

it('removes transfer from the queue manager contract', function () {
    $manager = app(QueueManager::class);

    expect(method_exists($manager, 'transfer'))->toBeFalse();
});

it('marks overdue called tickets as no-show via artisan command', function () {
    $service = Service::factory()->create(['code' => 'N']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);

    QueueTicket::query()->whereKey($called->ticket->id)->update([
        'called_at' => now()->subMinutes(10),
    ]);

    config(['queue_system.called_no_show_after_minutes' => 5]);

    Artisan::call('queue:auto-no-show');

    expect($called->ticket->fresh()->status)->toBe(QueueTicketStatus::NoShow);
});

it('skips auto no-show when timeout is disabled', function () {
    $service = Service::factory()->create(['code' => 'Z']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);

    QueueTicket::query()->whereKey($called->ticket->id)->update([
        'called_at' => now()->subMinutes(30),
    ]);

    config(['queue_system.called_no_show_after_minutes' => 0]);

    Artisan::call('queue:auto-no-show');

    expect($called->ticket->fresh()->status)->toBe(QueueTicketStatus::Called);
});

it('authorizes ticket manage only for the counter claimer', function () {
    $claimer = User::factory()->create();
    $other = User::factory()->create();
    $service = Service::factory()->create();
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->claimCounter($counter, $claimer);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter, $claimer);

    expect($claimer->can('manage', $called->ticket))->toBeTrue()
        ->and($other->can('manage', $called->ticket))->toBeFalse();
});

it('blocks operator actions against stale or inactive tickets', function () {
    $claimer = User::factory()->create();
    $service = Service::factory()->create();
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->claimCounter($counter, $claimer);

    $staleTicket = QueueTicket::factory()
        ->for($service)
        ->for($counter)
        ->create([
            'status' => QueueTicketStatus::Called,
            'queue_date' => today()->subDay()->toDateString(),
            'called_at' => now()->subDay(),
        ]);

    $waitingTicket = QueueTicket::factory()
        ->for($service)
        ->for($counter)
        ->create([
            'status' => QueueTicketStatus::Waiting,
            'queue_date' => today()->toDateString(),
        ]);

    expect($claimer->can('manage', $staleTicket))->toBeFalse()
        ->and($claimer->can('manage', $waitingTicket))->toBeFalse();
});

it('rejects no-op queue status transitions', function () {
    $ticket = QueueTicket::factory()->create(['status' => QueueTicketStatus::Waiting]);

    expect(fn () => app(QueueTransitionValidator::class)
        ->assertCanTransition($ticket, QueueTicketStatus::Waiting))
        ->toThrow(InvalidQueueTransitionException::class);
});
