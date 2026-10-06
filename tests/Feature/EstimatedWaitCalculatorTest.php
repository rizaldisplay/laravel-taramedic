<?php

use App\Enums\QueueTicketPriority;
use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use App\Services\EstimatedWaitCalculator;
use App\Services\QueueManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('estimates wait using priority ordering', function () {
    $service = Service::factory()->create(['code' => 'P', 'estimated_minutes' => 5]);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $priorityTicket = $manager->issueFromOperator(
        $service,
        User::factory()->create(),
        QueueTicketPriority::Priority,
    )->ticket;
    $normalTicket = $manager->issueFromKiosk($service)->ticket;

    $calculator = app(EstimatedWaitCalculator::class);

    // Priority sits ahead of both normals → no priority ahead → 0
    expect($calculator->forTicket($priorityTicket))->toBe(0);

    // Latest normal has: 1 normal ahead + 1 priority ahead = 2 → ceil(2/1)*5 = 10
    expect($calculator->forTicket($normalTicket))->toBe(10);
});

it('divides workload by open counters for the service', function () {
    $service = Service::factory()->create(['code' => 'D', 'estimated_minutes' => 10]);
    $first = Counter::factory()->create(['code' => 'C1']);
    $second = Counter::factory()->create(['code' => 'C2']);
    $first->services()->attach($service);
    $second->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $manager->issueFromKiosk($service);
    $newest = $manager->issueFromKiosk($service)->ticket;

    // 4 ahead / 2 counters = 2 slots * 10 minutes = 20
    expect(app(EstimatedWaitCalculator::class)->forTicket($newest))->toBe(20);
});

it('includes in-progress tickets in the workload', function () {
    $service = Service::factory()->create(['code' => 'I', 'estimated_minutes' => 5]);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $manager->issueFromKiosk($service);
    $waiting = $manager->issueFromKiosk($service)->ticket;
    $manager->callNext($counter);

    // 0 waiting ahead + 1 in progress → ceil(1/1)*5 = 5
    expect(app(EstimatedWaitCalculator::class)->forTicket($waiting))->toBe(5);
});

it('batch estimates visible tickets with the same priority and capacity rules', function () {
    $service = Service::factory()->create(['code' => 'M', 'estimated_minutes' => 5]);
    $counter = Counter::factory()->create();
    $counter->services()->attach($service);

    $manager = app(QueueManager::class);
    $first = $manager->issueFromKiosk($service)->ticket;
    $priority = $manager->issueFromOperator(
        $service,
        User::factory()->create(),
        QueueTicketPriority::Priority,
    )->ticket;
    $last = $manager->issueFromKiosk($service)->ticket;

    $estimates = app(EstimatedWaitCalculator::class)->forTickets(collect([$first, $priority, $last]));

    expect($estimates[$first->id])->toBe(5)
        ->and($estimates[$priority->id])->toBe(0)
        ->and($estimates[$last->id])->toBe(10);
});
