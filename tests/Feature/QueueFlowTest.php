<?php

use App\Enums\QueueCallType;
use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Events\QueueTicketCalled;
use App\Events\QueueTicketIssued;
use App\Events\QueueTicketStatusChanged;
use App\Exceptions\Queue\CounterHasActiveTicketException;
use App\Exceptions\Queue\InvalidQueueTransitionException;
use App\Exceptions\Queue\NoWaitingTicketException;
use App\Models\Counter;
use App\Models\QueueCall;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('issues ticket from kiosk with sequential numbers', function () {
    Event::fake([QueueTicketIssued::class]);

    $service = Service::factory()->create(['code' => 'A']);
    $manager = app(QueueManager::class);

    $first = $manager->issueFromKiosk($service);
    $second = $manager->issueFromKiosk($service, QueueTicketPriority::Priority);

    expect($first->ticket->ticket_number)->toBe('A001')
        ->and($second->ticket->ticket_number)->toBe('A002')
        ->and($second->ticket->source)->toBe(QueueTicketSource::Kiosk)
        ->and($second->ticket->priority)->toBe(QueueTicketPriority::Normal);

    Event::assertDispatched(QueueTicketIssued::class, 2);
});

it('ignores priority flag on kiosk issuance', function () {
    $service = Service::factory()->create(['code' => 'A2']);

    $result = app(QueueManager::class)->issueFromKiosk($service, QueueTicketPriority::Priority);

    expect($result->ticket->priority)->toBe(QueueTicketPriority::Normal);
});

it('issues priority tickets from operator only', function () {
    $operator = User::factory()->create();
    $service = Service::factory()->create(['code' => 'A3']);

    $result = app(QueueManager::class)->issueFromOperator(
        $service,
        $operator,
        QueueTicketPriority::Priority,
    );

    expect($result->ticket->priority)->toBe(QueueTicketPriority::Priority)
        ->and($result->ticket->source)->toBe(QueueTicketSource::Operator);
});

it('issues ticket from operator with creator reference', function () {
    $operator = User::factory()->create();
    $service = Service::factory()->create(['code' => 'B']);

    $result = app(QueueManager::class)->issueFromOperator($service, $operator);

    expect($result->ticket->source)->toBe(QueueTicketSource::Operator)
        ->and($result->ticket->created_by_user_id)->toBe($operator->id);
});

it('allows daily issuance without per-service quota configuration', function () {
    $service = Service::factory()->create(['code' => 'C']);
    $manager = app(QueueManager::class);

    $first = $manager->issueFromKiosk($service);
    $second = $manager->issueFromKiosk($service);

    expect($first->ticket->ticket_number)->toBe('C001')
        ->and($second->ticket->ticket_number)->toBe('C002');
});

it('calls next ticket with priority ordering', function () {
    Event::fake([QueueTicketCalled::class]);

    $service = Service::factory()->create(['code' => 'D']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    QueueTicket::factory()->for($service)->create([
        'ticket_number' => 'D001',
        'sequence' => 1,
        'priority' => QueueTicketPriority::Normal,
    ]);

    QueueTicket::factory()->for($service)->create([
        'ticket_number' => 'D002',
        'sequence' => 2,
        'priority' => QueueTicketPriority::Priority,
    ]);

    $called = app(QueueManager::class)->callNext($counter);

    expect($called->ticket->ticket_number)->toBe('D002')
        ->and($called->ticket->status)->toBe(QueueTicketStatus::Called)
        ->and($called->call->call_type)->toBe(QueueCallType::Initial)
        ->and($called->announcement->message)->toContain('D002')
        ->and($called->announcement->toArray())->toMatchArray([
            'call_id' => $called->call->id,
            'called_at' => $called->call->called_at->toIso8601String(),
            'service_code' => 'D',
            'counter_code' => $counter->code,
        ])
        ->and($called->announcement->toArray())->not->toHaveKeys(['voice_mode', 'audio_url']);

    Event::assertDispatched(QueueTicketCalled::class);
});

it('throws when no waiting tickets exist', function () {
    $service = Service::factory()->create();
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    expect(fn () => app(QueueManager::class)->callNext($counter))
        ->toThrow(NoWaitingTicketException::class);
});

it('completes full operator flow call serve complete', function () {
    Event::fake([QueueTicketCalled::class, QueueTicketStatusChanged::class]);

    $operator = User::factory()->create();
    $service = Service::factory()->create(['code' => 'E']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $issued = app(QueueManager::class)->issueFromKiosk($service);
    $manager = app(QueueManager::class);

    $called = $manager->callNext($counter, $operator);
    $serving = $manager->startServing($called->ticket, $operator);
    $completed = $manager->complete($serving, $operator);

    expect($completed->status)->toBe(QueueTicketStatus::Completed)
        ->and($completed->handled_by_user_id)->toBe($operator->id)
        ->and($completed->completed_at)->not->toBeNull();
});

it('defers ticket back to waiting queue on skip', function () {
    $service = Service::factory()->create(['code' => 'F']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $issued = $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $deferred = $manager->defer($called->ticket);

    expect($deferred->status)->toBe(QueueTicketStatus::Waiting)
        ->and($deferred->skip_count)->toBe(1)
        ->and($deferred->counter_id)->toBeNull()
        ->and($deferred->called_at)->toBeNull();
});

it('recalls a called ticket and increments recall count', function () {
    $service = Service::factory()->create(['code' => 'G']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $recalled = $manager->recall($called->ticket, $counter);

    expect($recalled->ticket->recall_count)->toBe(1)
        ->and($recalled->call->call_type)->toBe(QueueCallType::Recall);
});

it('deduplicates rapid recall requests inside the configured window', function () {
    $service = Service::factory()->create(['code' => 'G2']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);

    config(['queue_system.recall_deduplication_seconds' => 2]);
    Event::fake([QueueTicketCalled::class]);

    $firstRecall = $manager->recall($called->ticket, $counter);
    $duplicateRecall = $manager->recall($called->ticket, $counter);

    expect($duplicateRecall->call->id)->toBe($firstRecall->call->id)
        ->and($called->ticket->fresh()->recall_count)->toBe(1)
        ->and(QueueCall::query()
            ->where('queue_ticket_id', $called->ticket->id)
            ->where('call_type', QueueCallType::Recall->value)
            ->count())->toBe(1);

    Event::assertDispatched(QueueTicketCalled::class, 1);
});

it('allows another recall after the deduplication window', function () {
    $this->travelTo(now()->startOfSecond());

    $service = Service::factory()->create(['code' => 'G3']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    config(['queue_system.recall_deduplication_seconds' => 2]);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $firstRecall = $manager->recall($called->ticket, $counter);

    $this->travel(3)->seconds();

    $secondRecall = $manager->recall($called->ticket, $counter);

    expect($secondRecall->call->id)->not->toBe($firstRecall->call->id)
        ->and($called->ticket->fresh()->recall_count)->toBe(2)
        ->and(QueueCall::query()
            ->where('queue_ticket_id', $called->ticket->id)
            ->where('call_type', QueueCallType::Recall->value)
            ->count())->toBe(2);
});

it('marks ticket as no show', function () {
    $service = Service::factory()->create(['code' => 'H']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $noShow = $manager->markNoShow($called->ticket);

    expect($noShow->status)->toBe(QueueTicketStatus::NoShow)
        ->and($noShow->completed_at)->not->toBeNull();
});

it('rejects recall when ticket is serving', function () {
    $service = Service::factory()->create(['code' => 'H2']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $serving = $manager->startServing($called->ticket);

    expect(fn () => $manager->recall($serving, $counter))
        ->toThrow(InvalidQueueTransitionException::class);
});

it('blocks call next while counter still has an active ticket', function () {
    $service = Service::factory()->create(['code' => 'H3']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $manager->callNext($counter);

    expect(fn () => $manager->callNext($counter))
        ->toThrow(CounterHasActiveTicketException::class);
});

it('rejects completing an already completed ticket', function () {
    $service = Service::factory()->create(['code' => 'H4']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $completed = $manager->complete($manager->startServing($called->ticket));

    expect(fn () => $manager->complete($completed))
        ->toThrow(InvalidQueueTransitionException::class);
});

it('builds display board data without exposing waiting tickets', function () {
    $service = Service::factory()->create(['code' => 'I', 'name' => 'Pendaftaran']);
    $counter = Counter::factory()->create(['name' => 'Loket 1']);
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $manager->callNext($counter);

    $board = $manager->displayBoard();
    $payload = $board->toArray();

    expect($payload['now_serving'])->toHaveCount(1)
        ->and($payload['recent_calls'])->toHaveCount(0)
        ->and($payload)->not->toHaveKey('upcoming')
        ->and($payload['now_serving'][0]['service_name'])->toBe('Pendaftaran');
});

it('provides queue statistics for today', function () {
    $service = Service::factory()->create(['code' => 'J']);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $called = $manager->callNext($counter);
    $manager->complete($manager->startServing($called->ticket));

    $stats = $manager->statistics();

    expect($stats->total)->toBe(1)
        ->and($stats->completed)->toBe(1)
        ->and($stats->waiting)->toBe(0);
});
