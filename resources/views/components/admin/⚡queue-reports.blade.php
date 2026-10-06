<?php

use App\Enums\QueueTicketSource;
use App\Enums\QueueTicketStatus;
use App\Models\Counter;
use App\Models\Service;
use App\Services\QueueManager;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public ?int $serviceId = null;

    #[Url]
    public ?int $counterId = null;

    #[Url]
    public string $status = '';

    #[Url]
    public string $source = '';

    public function mount(): void
    {
        Gate::authorize('access-admin');
        $this->dateFrom = $this->dateFrom !== '' ? $this->dateFrom : today()->toDateString();
        $this->dateTo = $this->dateTo !== '' ? $this->dateTo : today()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'serviceId', 'counterId', 'status', 'source'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        Gate::authorize('access-admin');
        $this->dateFrom = today()->toDateString();
        $this->dateTo = today()->toDateString();
        $this->serviceId = null;
        $this->counterId = null;
        $this->status = '';
        $this->source = '';
        $this->resetPage();
    }

    public function with(QueueManager $queue): array
    {
        $selectedStatus = QueueTicketStatus::tryFrom($this->status);
        $selectedSource = QueueTicketSource::tryFrom($this->source);

        $filters = [
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
            'service_id' => $this->serviceId,
            'counter_id' => $this->counterId,
            'status' => $selectedStatus,
            'source' => $selectedSource,
        ];

        $summary = $queue->reportSummary($filters);

        return [
            'tickets' => $queue->report($filters, 20),
            'services' => Service::query()->ordered()->get(['id', 'name', 'code']),
            'counters' => Counter::query()->ordered()->get(['id', 'name', 'code']),
            'statuses' => QueueTicketStatus::cases(),
            'sources' => QueueTicketSource::cases(),
            'summary' => $summary->toArray(),
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Laporan Antrian',
            'subtitle' => 'Riwayat tiket dan ringkasan performa layanan',
        ])
    </div>

    <x-admin.guide
        title="Membaca laporan dengan cepat"
        description="Mulai dari rentang tanggal, lalu persempit hanya jika Anda mencari kasus tertentu."
        :items="[
            'Gunakan ringkasan untuk melihat beban dan hasil layanan.',
            'Filter layanan atau loket untuk mengevaluasi area tertentu.',
            'Bandingkan waktu terbit, dipanggil, dan selesai pada tabel riwayat.',
        ]"
    />

    <div class="admin-card mb-6">
        <div class="admin-card-header">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Filter Laporan</h2>
                <p class="text-xs text-gray-500">Sempitkan data berdasarkan tanggal, layanan, loket, status, atau sumber</p>
            </div>
            <button type="button" wire:click="resetFilters" class="admin-btn-secondary">Reset</button>
        </div>
        <div class="admin-card-body">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="admin-form-label">Dari tanggal</label>
                    <input type="date" wire:model.live="dateFrom" class="admin-form-input">
                </div>
                <div>
                    <label class="admin-form-label">Sampai tanggal</label>
                    <input type="date" wire:model.live="dateTo" class="admin-form-input">
                </div>
                <div>
                    <label class="admin-form-label">Layanan</label>
                    <select wire:model.live="serviceId" class="admin-form-input">
                        <option value="">Semua layanan</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->code }} — {{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-form-label">Loket</label>
                    <select wire:model.live="counterId" class="admin-form-input">
                        <option value="">Semua loket</option>
                        @foreach ($counters as $counter)
                            <option value="{{ $counter->id }}">{{ $counter->code }} — {{ $counter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-form-label">Status</label>
                    <select wire:model.live="status" class="admin-form-input">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-form-label">Sumber</label>
                    <select wire:model.live="source" class="admin-form-input">
                        <option value="">Semua sumber</option>
                        @foreach ($sources as $item)
                            <option value="{{ $item->value }}">{{ ucfirst($item->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        @foreach ([
            ['Total', $summary['total'] ?? 0, 'brand'],
            ['Menunggu', $summary['waiting'] ?? 0, 'warning'],
            ['Selesai', $summary['completed'] ?? 0, 'success'],
            ['Tidak Hadir', $summary['no_show'] ?? 0, 'danger'],
            ['Avg Tunggu', isset($summary['average_wait_minutes']) ? $summary['average_wait_minutes'].' m' : '-', 'info'],
        ] as [$label, $value, $tone])
            @php
                $bar = match ($tone) {
                    'brand' => 'bg-brand-500',
                    'warning' => 'bg-orange-500',
                    'success' => 'bg-success-500',
                    'danger' => 'bg-error-500',
                    'info' => 'bg-sky-500',
                    default => 'bg-gray-400',
                };
            @endphp
            <div class="admin-stat-card !p-4" wire:key="report-metric-{{ $label }}">
                <div class="absolute inset-x-0 top-0 h-1 {{ $bar }}"></div>
                <p class="admin-stat-label text-xs uppercase tracking-wide">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="admin-table-wrap">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Layanan</th>
                        <th>Loket</th>
                        <th>Status</th>
                        <th>Sumber</th>
                        <th>Terbit</th>
                        <th>Dipanggil</th>
                        <th>Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr wire:key="report-{{ $ticket->id }}">
                            <td class="font-semibold text-gray-900 dark:text-white">{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->service?->name }}</td>
                            <td>{{ $ticket->counter?->name ?? '—' }}</td>
                            <td>{{ $ticket->status->label() }}</td>
                            <td>{{ $ticket->source->value }}</td>
                            <td class="text-gray-500">{{ $ticket->issued_at?->format('d/m H:i') }}</td>
                            <td class="text-gray-500">{{ $ticket->called_at?->format('d/m H:i') ?? '—' }}</td>
                            <td class="text-gray-500">{{ $ticket->completed_at?->format('d/m H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-500">Tidak ada data untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
