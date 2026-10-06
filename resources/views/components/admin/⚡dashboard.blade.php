<?php

use App\Services\QueueManager;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        Gate::authorize('access-admin');
        $this->loadStats();
    }

    #[On('queue-updated')]
    public function loadStats(): void
    {
        Gate::authorize('access-admin');
        $this->stats = app(QueueManager::class)->statistics()->toArray();
    }

    public function with(): array
    {
        return [
            'institutionName' => app(SettingsService::class)->institutionName(),
        ];
    }
};
?>

<div wire:poll.10s="loadStats" data-queue-realtime="admin">
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Dashboard',
            'subtitle' => $institutionName.' · Monitoring antrian real-time hari ini',
        ])
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('operator.panel') }}" class="admin-btn-primary">Panel Operator</a>
            <a href="{{ route('admin.reports') }}" class="admin-btn-secondary">Lihat Laporan</a>
        </div>
    </div>

    <x-admin.guide
        title="Alur kerja harian"
        description="Gunakan dashboard sebagai titik awal sebelum pelayanan dibuka."
        :items="[
            'Pastikan layanan dan loket yang digunakan hari ini berstatus aktif.',
            'Buka kiosk serta monitor ruang tunggu pada perangkat masing-masing.',
            'Minta operator masuk ke Panel Operator dan mengklaim loketnya.',
        ]"
    />

    @php
        $metrics = [
            ['label' => 'Total Hari Ini', 'value' => $stats['total'] ?? 0, 'tone' => 'brand', 'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Menunggu', 'value' => $stats['waiting'] ?? 0, 'tone' => 'warning', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Dipanggil', 'value' => $stats['called'] ?? 0, 'tone' => 'info', 'path' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
            ['label' => 'Selesai', 'value' => $stats['completed'] ?? 0, 'tone' => 'success', 'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Tidak Hadir', 'value' => $stats['no_show'] ?? 0, 'tone' => 'danger', 'path' => 'M6 18L18 6M6 6l12 12'],
        ];

        $toneMap = [
            'brand' => ['bar' => 'bg-brand-500', 'icon' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300'],
            'warning' => ['bar' => 'bg-orange-500', 'icon' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300'],
            'info' => ['bar' => 'bg-sky-500', 'icon' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300'],
            'success' => ['bar' => 'bg-success-500', 'icon' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-300'],
            'danger' => ['bar' => 'bg-error-500', 'icon' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-300'],
        ];

        $primaryLinks = [
            ['route' => 'operator.panel', 'label' => 'Panel Operator', 'desc' => 'Panggil & layani antrian'],
            ['route' => 'kiosk', 'label' => 'Kios Mandiri', 'desc' => 'Layar pengambilan nomor', 'external' => true],
            ['route' => 'display', 'label' => 'Monitor Display', 'desc' => 'Layar ruang tunggu', 'external' => true],
        ];

    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($metrics as $metric)
            @php $tone = $toneMap[$metric['tone']]; @endphp
            <div class="admin-stat-card group" wire:key="metric-{{ $metric['label'] }}">
                <div class="absolute inset-x-0 top-0 h-1 {{ $tone['bar'] }}"></div>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="admin-stat-label">{{ $metric['label'] }}</p>
                        <p class="admin-stat-value">{{ $metric['value'] }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone['icon'] }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $metric['path'] }}"/>
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
        <div class="admin-card xl:col-span-3">
            <div class="admin-card-header">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Performa per Layanan</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Distribusi antrian hari ini</p>
                </div>
                <span class="rounded-lg bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    Avg tunggu: <strong class="text-gray-900 dark:text-white">{{ isset($stats['average_wait_minutes']) ? $stats['average_wait_minutes'].' mnt' : '—' }}</strong>
                </span>
            </div>
            <div class="admin-card-body space-y-3">
                @forelse ($stats['by_service'] ?? [] as $row)
                    @php
                        $waiting = (int) ($row['waiting'] ?? 0);
                        $completed = (int) ($row['completed'] ?? 0);
                        $total = max($waiting + $completed, 1);
                        $pct = (int) round(($completed / $total) * 100);
                    @endphp
                    <div class="rounded-2xl border border-gray-100 p-4 transition hover:border-brand-200 dark:border-gray-800 dark:hover:border-brand-500/30">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $row['service_name'] }}</span>
                            <div class="flex gap-3 text-sm">
                                <span class="font-medium text-orange-600 dark:text-orange-400">{{ $waiting }} tunggu</span>
                                <span class="font-medium text-success-600 dark:text-success-400">{{ $completed }} selesai</span>
                            </div>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="admin-empty-state py-12">
                        <p class="text-sm text-gray-500">Belum ada data layanan hari ini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Akses Operasional</h2>
                </div>
                <div class="admin-card-body space-y-2">
                    @foreach ($primaryLinks as $link)
                        <a
                            wire:key="primary-link-{{ $link['route'] }}"
                            href="{{ route($link['route']) }}"
                            @if (! empty($link['external'])) target="_blank" rel="noopener" @endif
                            class="admin-quick-link admin-quick-link--primary"
                        >
                            <span class="text-sm font-semibold text-brand-800 dark:text-brand-200">{{ $link['label'] }}</span>
                            <span class="text-xs text-brand-700/70 dark:text-brand-300/70">{{ $link['desc'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>
