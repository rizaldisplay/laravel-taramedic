<?php

use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public int $estimated_minutes = 5;

    public int $sort_order = 0;

    public bool $is_active = true;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('access-admin');
    }

    public function create(): void
    {
        Gate::authorize('access-admin');
        $this->resetForm();
        $this->editingId = 0;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        Gate::authorize('access-admin');
        $this->resetValidation();
        $service = Service::query()->findOrFail($id);
        $this->editingId = $id;
        $this->code = $service->code;
        $this->name = $service->name;
        $this->estimated_minutes = $service->estimated_minutes ?? 5;
        $this->sort_order = $service->sort_order;
        $this->is_active = $service->is_active;
    }

    public function save(): void
    {
        Gate::authorize('access-admin');
        $this->code = mb_strtoupper(trim($this->code));
        $this->name = trim($this->name);

        $data = $this->validate([
            'code' => ['required', 'string', 'max:3', Rule::unique('services', 'code')->ignore($this->editingId ?: null)],
            'name' => ['required', 'string', 'max:255'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingId) {
            Service::query()->findOrFail($this->editingId)->update($data);
        } else {
            Service::query()->create($data);
        }

        $this->resetForm();
        session()->flash('message', 'Layanan berhasil disimpan.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('access-admin');
        $service = Service::query()->findOrFail($id);

        if ($service->queueTickets()->exists()) {
            $service->update(['is_active' => false]);
            session()->flash('message', 'Layanan memiliki riwayat antrean dan telah dinonaktifkan.');

            return;
        }

        $service->counters()->detach();
        $service->delete();
        session()->flash('message', 'Layanan dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->reset(['code', 'name', 'estimated_minutes', 'sort_order', 'is_active']);
        $this->estimated_minutes = 5;
        $this->is_active = true;
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'services' => Service::query()
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }))
                ->ordered()
                ->get(),
            'serviceCount' => Service::query()->count(),
            'activeServiceCount' => Service::query()->active()->count(),
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Kelola Layanan',
            'subtitle' => 'Konfigurasi jenis layanan antrian di kios dan operator',
        ])
        <button type="button" wire:click="create" class="admin-btn-primary shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Tambah Layanan
        </button>
    </div>

    @include('partials.admin.flash')

    <x-admin.guide
        title="Cara mengatur layanan"
        description="Layanan adalah pilihan yang dilihat pasien pada kiosk."
        :items="[
            'Gunakan kode pendek dan mudah dibaca, misalnya A atau B.',
            'Estimasi membantu pasien memahami perkiraan waktu layanan.',
            'Nonaktifkan layanan bila sementara ditutup agar tidak muncul di kiosk.',
        ]"
    />

    <div class="admin-table-wrap">
        <div class="admin-table-toolbar">
            <div class="admin-table-search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari kode atau nama layanan..." aria-label="Cari layanan">
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500">
                <span class="admin-stat-chip">{{ $activeServiceCount }} aktif</span>
                <span>{{ $serviceCount }} total layanan</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Estimasi</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr wire:key="service-row-{{ $service->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-700 font-display text-sm font-bold text-white shadow-sm dark:bg-brand-400 dark:text-ink-950">{{ $service->code }}</span>
                                    <div>
                                        <p class="font-bold text-ink-950 dark:text-white">{{ $service->name }}</p>
                                        <p class="mt-0.5 text-xs text-ink-400">Kode antrean {{ $service->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="font-semibold tabular-nums">{{ $service->estimated_minutes }} menit</span></td>
                            <td><span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-ink-100 px-2 font-bold tabular-nums text-ink-600 dark:bg-white/7 dark:text-ink-300">{{ $service->sort_order }}</span></td>
                            <td>
                                <span @class(['admin-badge-active' => $service->is_active, 'admin-badge-inactive' => ! $service->is_active])>{{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $service->id }})" class="admin-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $service->id }})" wire:confirm="Hapus layanan {{ $service->name }}?" class="admin-btn-danger">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="admin-empty-state !border-0 !bg-transparent py-12">
                                    <p class="font-semibold text-ink-700 dark:text-ink-200">{{ $search !== '' ? 'Layanan tidak ditemukan' : 'Belum ada layanan' }}</p>
                                    <p class="mt-1 text-sm text-ink-400">{{ $search !== '' ? 'Coba gunakan kata pencarian lain.' : 'Tambahkan layanan pertama untuk mulai menerima antrean.' }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($editingId !== null)
        <x-admin.modal
            :title="$editingId ? 'Edit layanan' : 'Tambah layanan'"
            description="Informasi ini akan digunakan pada kiosk, panel operator, dan tiket pasien."
        >
            <form id="service-form" wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="service-code" class="admin-form-label">Kode antrean</label>
                    <input id="service-code" wire:model="code" class="admin-form-input font-display font-bold uppercase" maxlength="3" placeholder="Contoh: A" autofocus>
                    <p class="admin-form-hint">Maksimal 3 karakter. Pilih kode yang mudah terlihat dari jauh.</p>
                    @error('code') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="service-name" class="admin-form-label">Nama layanan</label>
                    <input id="service-name" wire:model="name" class="admin-form-input" placeholder="Contoh: Poli Umum">
                    <p class="admin-form-hint">Gunakan istilah yang familiar bagi pasien.</p>
                    @error('name') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="service-estimate" class="admin-form-label">Estimasi layanan</label>
                    <div class="relative">
                        <input id="service-estimate" type="number" wire:model="estimated_minutes" min="1" max="120" class="admin-form-input pr-16">
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs font-bold text-ink-400">menit</span>
                    </div>
                    <p class="admin-form-hint">Rata-rata waktu pelayanan satu pasien.</p>
                    @error('estimated_minutes') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="service-order" class="admin-form-label">Urutan tampilan</label>
                    <input id="service-order" type="number" wire:model="sort_order" min="0" max="999" class="admin-form-input">
                    <p class="admin-form-hint">Angka kecil tampil lebih dahulu pada kiosk.</p>
                    @error('sort_order') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div class="admin-field-group sm:col-span-2">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model="is_active" class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm font-bold text-ink-800 dark:text-white">Layanan aktif</span>
                            <span class="mt-1 block text-xs leading-5 text-ink-500">Layanan aktif dapat dipilih pasien dan diproses operator.</span>
                        </span>
                    </label>
                    @error('is_active') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
            </form>

            <x-slot:footer>
                <button type="button" wire:click="cancelEdit" class="admin-btn-secondary">Batal</button>
                <button type="submit" form="service-form" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Simpan Layanan</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
