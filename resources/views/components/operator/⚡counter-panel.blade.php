<?php

use App\Enums\QueueTicketPriority;
use App\Enums\QueueTicketStatus;
use App\Exceptions\Queue\QueueDomainException;
use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $counterId = null;

    public ?int $issueServiceId = null;

    public bool $issuePriority = false;

    public ?string $flashMessage = null;

    public ?string $errorMessage = null;

    public function mount(QueueManager $queue): void
    {
        $user = $this->operator();

        $claimed = Counter::query()
            ->active()
            ->where('claimed_by_user_id', $user->id)
            ->ordered()
            ->first();

        if ($claimed !== null) {
            $this->counterId = $claimed->id;

            return;
        }

        $available = Counter::query()->active()->ordered()->unclaimed()->first();

        if ($available !== null) {
            $this->selectCounter($available->id, $queue);

            return;
        }

        if ($user->role->canAccessAdmin()) {
            $any = Counter::query()->active()->ordered()->first();

            if ($any !== null) {
                $this->selectCounter($any->id, $queue);
            }
        }
    }

    public function selectCounter(int $counterId, QueueManager $queue): void
    {
        $this->resetMessages();

        $counter = Counter::query()->active()->findOrFail($counterId);
        $user = $this->operator();
        $force = $user->can('forceClaim', $counter);

        try {
            $queue->claimCounter($counter, $user, $force);
            $this->counterId = $counter->id;
            $this->flashMessage = "Loket {$counter->name} diklaim.";
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function releaseClaim(QueueManager $queue): void
    {
        $this->resetMessages();

        if ($this->counterId === null) {
            return;
        }

        try {
            $counter = Counter::query()->findOrFail($this->counterId);
            $user = $this->operator();
            $queue->releaseCounter($counter, $user, $user->can('forceClaim', $counter));
            $this->flashMessage = "Klaim loket {$counter->name} dilepas.";
            $this->counterId = null;
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function callNext(QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $counter = $this->authorizedCounter();
            $result = $queue->callNext($counter, $this->operator());
            $this->flashMessage = "Memanggil {$result->ticket->ticket_number}";

            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function recall(int $ticketId, QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $ticket = $this->authorizedTicket($ticketId);
            $result = $queue->recall($ticket, $this->authorizedCounter(), $this->operator());
            $this->flashMessage = "Panggil ulang {$result->ticket->ticket_number}";

            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function deferTicket(int $ticketId, QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $ticket = $this->authorizedTicket($ticketId);
            $queue->defer($ticket);
            $this->flashMessage = "Antrian {$ticket->ticket_number} ditunda.";
            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function markNoShow(int $ticketId, QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $ticket = $this->authorizedTicket($ticketId);
            $queue->markNoShow($ticket);
            $this->flashMessage = "Antrian {$ticket->ticket_number} ditandai tidak hadir.";
            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function startServing(int $ticketId, QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $ticket = $this->authorizedTicket($ticketId);
            $queue->startServing($ticket, $this->operator());
            $this->flashMessage = "Melayani {$ticket->ticket_number}.";
            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function complete(int $ticketId, QueueManager $queue): void
    {
        $this->resetMessages();

        try {
            $ticket = $this->authorizedTicket($ticketId);
            $queue->complete($ticket, $this->operator());
            $this->flashMessage = "Antrian {$ticket->ticket_number} selesai.";
            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function issueForVisitor(QueueManager $queue): void
    {
        $this->resetMessages();

        if ($this->issueServiceId === null) {
            $this->errorMessage = 'Pilih layanan untuk pengunjung.';

            return;
        }

        try {
            $this->authorizedCounter();
            $service = Service::query()->active()->findOrFail($this->issueServiceId);
            $result = $queue->issueFromOperator(
                $service,
                $this->operator(),
                $this->issuePriority ? QueueTicketPriority::Priority : QueueTicketPriority::Normal,
            );

            $this->flashMessage = "Nomor {$result->ticket->ticket_number} diterbitkan.";
            $this->issueServiceId = null;
            $this->issuePriority = false;
            $this->dispatch('queue-updated');
        } catch (QueueDomainException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    #[On('queue-updated')]
    public function refreshPanel(): void
    {
        // Memicu re-render Livewire agar antrian menunggu/aktif terbaru dimuat.
    }

    private function authorizedCounter(): Counter
    {
        $counter = Counter::query()->active()->findOrFail($this->counterId);
        $this->authorize('operate', $counter);

        return $counter;
    }

    private function authorizedTicket(int $ticketId): QueueTicket
    {
        $ticket = QueueTicket::query()
            ->forToday()
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->findOrFail($ticketId);

        $this->authorize('manage', $ticket);

        return $ticket;
    }

    private function operator(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function resetMessages(): void
    {
        $this->flashMessage = null;
        $this->errorMessage = null;
    }

    public function with(): array
    {
        $user = $this->operator();

        $serviceIds = $this->counterId
            ? Counter::query()->find($this->counterId)?->services()->pluck('services.id') ?? collect()
            : collect();

        $activeTickets = QueueTicket::query()
            ->forToday()
            ->whereIn('status', [QueueTicketStatus::Called, QueueTicketStatus::Serving])
            ->when($this->counterId, fn ($q) => $q->where('counter_id', $this->counterId))
            ->with(['service:id,name,code', 'counter:id,name,code'])
            ->latest('called_at')
            ->get();

        $currentTicket = $activeTickets->first();
        $selectedCounter = $this->counterId
            ? Counter::query()->with(['services:id,name,code', 'claimedBy:id,name'])->find($this->counterId)
            : null;

        return [
            'counters' => Counter::query()
                ->active()
                ->ordered()
                ->with(['services:id,name,code', 'claimedBy:id,name'])
                ->get(),
            'services' => Service::query()->active()->ordered()->get(),
            'waitingTickets' => QueueTicket::query()
                ->forToday()
                ->waiting()
                ->when($serviceIds->isNotEmpty(), fn ($q) => $q->whereIn('service_id', $serviceIds))
                ->with('service:id,name,code')
                ->orderedForCall()
                ->get(),
            'activeTickets' => $activeTickets,
            'currentTicket' => $currentTicket,
            'selectedCounter' => $selectedCounter,
            'ownsClaim' => $selectedCounter?->isClaimedBy($user) ?? false,
            'canForceClaim' => $user->role->canAccessAdmin(),
        ];
    }
};
?>

<div wire:poll.15s data-queue-realtime="operator">

    @if ($flashMessage)
        <div class="admin-toast-viewport" aria-live="polite">
            <div x-data="adminToast()" x-show="visible" x-cloak x-transition.opacity.scale.95 class="admin-toast admin-toast-success" role="status">
                <span class="admin-toast-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="admin-toast-title">Berhasil</span>
                    <span class="admin-toast-message">{{ $flashMessage }}</span>
                </span>
                <button type="button" class="admin-toast-close" @click="close()" aria-label="Tutup notifikasi">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <span class="admin-toast-progress" aria-hidden="true"></span>
            </div>
        </div>
    @endif

    @if ($errorMessage)
        <div class="admin-toast-viewport" aria-live="assertive">
            <div x-data="adminToast()" x-show="visible" x-cloak x-transition.opacity.scale.95 class="admin-toast admin-toast-error" role="alert">
                <span class="admin-toast-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="admin-toast-title">Perlu diperiksa</span>
                    <span class="admin-toast-message">{{ $errorMessage }}</span>
                </span>
                <button type="button" class="admin-toast-close" @click="close()" aria-label="Tutup notifikasi">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <span class="admin-toast-progress" aria-hidden="true"></span>
            </div>
        </div>
    @endif

    {{-- Counter selector --}}
    <div class="mb-6">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Loket Aktif</p>
            @if ($ownsClaim)
                <button type="button" wire:click="releaseClaim" class="text-xs font-medium text-gray-500 underline-offset-2 hover:underline dark:text-gray-400">
                    Lepas klaim loket
                </button>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($counters as $counter)
                @php
                    $isMine = $counter->isClaimedBy(auth()->user());
                    $isTaken = $counter->isClaimed() && ! $isMine;
                @endphp
                <button
                    type="button"
                    wire:click="selectCounter({{ $counter->id }})"
                    @class([
                        'operator-counter-pill',
                        'operator-counter-pill-active' => $counterId === $counter->id,
                        'operator-counter-pill-inactive' => $counterId !== $counter->id,
                        'opacity-60' => $isTaken && $counterId !== $counter->id,
                    ])
                    @disabled($isTaken && ! $canForceClaim && $counterId !== $counter->id)
                    title="{{ $isTaken ? 'Diklaim oleh '.$counter->claimedBy?->name : ($isMine ? 'Klaim Anda' : 'Tersedia') }}"
                >
                    {{ $counter->name }}
                    <span class="opacity-60">({{ $counter->code }})</span>
                    @if ($isTaken)
                        <span class="ml-1 text-[10px] uppercase tracking-wide opacity-80">{{ $counter->claimedBy?->name }}</span>
                    @elseif ($isMine)
                        <span class="ml-1 text-[10px] uppercase tracking-wide opacity-80">Anda</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div class="admin-card p-6 md:p-8">
                @if (! $ownsClaim && $counterId)
                    <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300">
                        Klaim loket ini terlebih dahulu untuk memanggil dan melayani antrian.
                    </div>
                @endif

                @if ($currentTicket)
                    <div class="mb-6 text-center">
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide',
                            'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' => $currentTicket->status === QueueTicketStatus::Called,
                            'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' => $currentTicket->status === QueueTicketStatus::Serving,
                        ])>
                            {{ $currentTicket->status->label() }}
                        </span>
                        <p class="mt-4 text-5xl font-bold tracking-tight text-brand-600 md:text-6xl">{{ $currentTicket->ticket_number }}</p>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-300">{{ $currentTicket->service->name }}</p>
                    </div>

                    <div class="flex flex-wrap justify-center gap-2">
                        @if ($currentTicket->status === QueueTicketStatus::Called)
                            <button wire:click="startServing({{ $currentTicket->id }})" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-600">
                                @include('partials.icons.check')
                                Pengunjung Hadir
                            </button>
                            <button
                                type="button"
                                wire:click="recall({{ $currentTicket->id }})"
                                wire:loading.attr="disabled"
                                wire:target="recall({{ $currentTicket->id }})"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium disabled:cursor-wait disabled:opacity-60 dark:border-gray-700"
                            >
                                @include('partials.icons.arrow-path')
                                <span wire:loading.remove wire:target="recall({{ $currentTicket->id }})">Panggil Ulang</span>
                                <span wire:loading wire:target="recall({{ $currentTicket->id }})">Memanggil...</span>
                            </button>
                            <button
                                wire:click="deferTicket({{ $currentTicket->id }})"
                                wire:confirm="Tunda antrian {{ $currentTicket->ticket_number }} dan kembalikan ke antrean?"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium dark:border-gray-700"
                            >
                                @include('partials.icons.pause')
                                Tunda
                            </button>
                            <button
                                wire:click="markNoShow({{ $currentTicket->id }})"
                                wire:confirm="Tandai {{ $currentTicket->ticket_number }} sebagai tidak hadir?"
                                class="inline-flex items-center gap-2 rounded-xl border border-error-200 px-5 py-3 text-sm font-medium text-error-600 dark:border-error-500/30"
                            >
                                @include('partials.icons.x-mark')
                                Tidak Hadir
                            </button>
                        @endif
                        @if ($currentTicket->status === QueueTicketStatus::Serving)
                            <button wire:click="complete({{ $currentTicket->id }})" class="inline-flex items-center gap-2 rounded-xl bg-success-500 px-6 py-3 text-sm font-semibold text-white hover:bg-success-600">
                                @include('partials.icons.check')
                                Selesai Dilayani
                            </button>
                        @endif
                    </div>

                @else
                    <div class="py-6 text-center">
                        <p class="text-gray-500 dark:text-gray-400">Belum ada antrian aktif di loket ini</p>
                        <p class="mt-1 text-sm text-gray-400">Tekan tombol di bawah untuk memanggil nomor berikutnya</p>
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="callNext"
                    wire:loading.attr="disabled"
                    class="operator-call-btn mt-6"
                    @disabled($currentTicket !== null || ! $ownsClaim)
                >
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span wire:loading.remove wire:target="callNext">Panggil Berikutnya</span>
                    <span wire:loading wire:target="callNext">Memanggil...</span>
                </button>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Antrian Menunggu</h3>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $waitingTickets->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">#</th>
                                <th class="px-5 py-3 font-medium">Nomor</th>
                                <th class="px-5 py-3 font-medium">Layanan</th>
                                <th class="px-5 py-3 font-medium">Prioritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($waitingTickets as $index => $ticket)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50" wire:key="waiting-{{ $ticket->id }}">
                                    <td class="px-5 py-3 text-gray-400">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3 text-lg font-bold text-gray-800 dark:text-white/90">{{ $ticket->ticket_number }}</td>
                                    <td class="px-5 py-3">{{ $ticket->service->name }}</td>
                                    <td class="px-5 py-3">
                                        @if ($ticket->priority === \App\Enums\QueueTicketPriority::Priority)
                                            <span class="rounded-full bg-orange-50 px-2 py-0.5 text-xs font-medium text-orange-600 dark:bg-orange-500/10 dark:text-orange-400">Prioritas</span>
                                        @else
                                            <span class="text-gray-400">Normal</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">Tidak ada antrian menunggu.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="admin-card p-5 md:p-6">
                <h3 class="text-theme-xl font-semibold text-gray-800 dark:text-white/90">Terbitkan Nomor</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Untuk pengunjung yang datang langsung ke loket</p>

                <select wire:model="issueServiceId" class="mt-4 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Pilih layanan</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">{{ $service->code }} — {{ $service->name }}</option>
                    @endforeach
                </select>

                <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" wire:model="issuePriority" class="rounded border-gray-300 text-brand-500">
                    Antrian prioritas
                </label>

                <button
                    type="button"
                    wire:click="issueForVisitor"
                    wire:loading.attr="disabled"
                    wire:target="issueForVisitor"
                    class="mt-4 w-full rounded-xl border-2 border-brand-500 py-3 text-sm font-semibold text-brand-600 hover:bg-brand-50 disabled:cursor-wait disabled:opacity-60 dark:text-brand-400 dark:hover:bg-brand-500/10"
                >
                    <span wire:loading.remove wire:target="issueForVisitor">Terbitkan Nomor</span>
                    <span wire:loading wire:target="issueForVisitor">Menerbitkan...</span>
                </button>
            </div>

            <div class="admin-card bg-brand-50/40 p-5 md:p-6 dark:bg-brand-500/5">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800 dark:text-brand-200">Alur Pelayanan</h3>
                <ol class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">1</span> Klaim loket Anda</li>
                    <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">2</span> Panggil nomor berikutnya</li>
                    <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500 text-xs font-bold text-white">3</span> Konfirmasi hadir, layani, selesai</li>
                </ol>
            </div>

            @if ($selectedCounter)
                <div class="admin-card p-5">
                    <h3 class="text-sm font-medium text-gray-500">Layanan di loket ini</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($selectedCounter->services as $service)
                            <span class="admin-stat-chip">{{ $service->code }}</span>
                        @empty
                            <span class="text-sm text-gray-400">Semua layanan</span>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
