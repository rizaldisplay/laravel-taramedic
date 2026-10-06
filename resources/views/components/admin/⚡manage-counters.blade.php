<?php

use App\Models\Counter;
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

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var array<int> */
    public array $serviceIds = [];

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
        $counter = Counter::query()->with('services')->findOrFail($id);
        $this->editingId = $id;
        $this->code = $counter->code;
        $this->name = $counter->name;
        $this->sort_order = $counter->sort_order;
        $this->is_active = $counter->is_active;
        $this->serviceIds = $counter->services->pluck('id')->all();
    }

    public function save(): void
    {
        Gate::authorize('access-admin');
        $this->code = mb_strtoupper(trim($this->code));
        $this->name = trim($this->name);

        $data = $this->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('counters', 'code')->ignore($this->editingId ?: null)],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'serviceIds' => ['required', 'array', 'min:1'],
            'serviceIds.*' => ['integer', 'exists:services,id'],
        ]);

        $serviceIds = $data['serviceIds'];
        unset($data['serviceIds']);

        if ($this->editingId) {
            $counter = Counter::query()->findOrFail($this->editingId);
            $counter->update($data);
        } else {
            $counter = Counter::query()->create($data);
        }

        $counter->services()->sync($serviceIds);
        $this->resetForm();
        session()->flash('message', 'Loket berhasil disimpan.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('access-admin');
        $counter = Counter::query()->findOrFail($id);

        if (
            $counter->queueTickets()->exists()
            || $counter->queueCalls()->exists()
        ) {
            $counter->update([
                'is_active' => false,
                'claimed_by_user_id' => null,
                'claimed_at' => null,
            ]);
            session()->flash('message', 'Loket memiliki riwayat dan telah dinonaktifkan.');

            return;
        }

        $counter->services()->detach();
        $counter->delete();
        session()->flash('message', 'Loket dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->reset(['code', 'name', 'sort_order', 'is_active', 'serviceIds']);
        $this->is_active = true;
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'counters' => Counter::query()
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                }))
                ->ordered()
                ->with(['services:id,name,code', 'claimedBy:id,name'])
                ->get(),
            'services' => Service::query()->active()->ordered()->get(['id', 'name', 'code']),
            'counterCount' => Counter::query()->count(),
            'activeCounterCount' => Counter::query()->active()->count(),
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Kelola Loket',
            'subtitle' => 'Konfigurasi loket pelayanan dan layanan yang ditangani',
        ])
        <button type="button" wire:click="create" class="admin-btn-primary shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Tambah Loket
        </button>
    </div>

    @include('partials.admin.flash')

    <x-admin.guide
        title="Cara mengatur loket"
        description="Loket adalah titik pelayanan yang akan dipilih operator saat mulai bekerja."
        :items="[
            'Beri nama sesuai penanda fisik, misalnya Loket Pendaftaran.',
            'Hubungkan minimal satu layanan yang dapat ditangani loket.',
            'Nonaktifkan loket saat tidak digunakan agar tidak dapat diklaim operator.',
        ]"
    />

    <div class="admin-table-wrap">
        <div class="admin-table-toolbar">
            <div class="admin-table-search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari kode atau nama loket..." aria-label="Cari loket">
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500">
                <span class="admin-stat-chip">{{ $activeCounterCount }} aktif</span>
                <span>{{ $counterCount }} total loket</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Loket</th>
                        <th>Layanan</th>
                        <th>Penggunaan</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($counters as $counter)
                        <tr wire:key="counter-row-{{ $counter->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 min-w-10 items-center justify-center rounded-xl bg-ink-950 px-2 font-display text-xs font-bold text-white dark:bg-brand-400 dark:text-ink-950">{{ $counter->code }}</span>
                                    <div>
                                        <p class="font-bold text-ink-950 dark:text-white">{{ $counter->name }}</p>
                                        <p class="mt-0.5 text-xs text-ink-400">Kode {{ $counter->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex min-w-44 flex-wrap gap-1.5">
                                    @forelse ($counter->services as $service)
                                        <span wire:key="counter-{{ $counter->id }}-service-{{ $service->id }}" class="rounded-lg bg-brand-50 px-2 py-1 text-[11px] font-bold text-brand-700 ring-1 ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20">{{ $service->code }} · {{ $service->name }}</span>
                                    @empty
                                        <span class="text-xs font-semibold text-error-500">Belum ada layanan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if ($counter->claimedBy)
                                    <div class="flex items-center gap-2 text-xs font-semibold text-ink-700 dark:text-ink-200">
                                        <span class="h-2 w-2 rounded-full bg-warning-400"></span>
                                        {{ $counter->claimedBy->name }}
                                    </div>
                                @else
                                    <span class="text-xs text-ink-400">Belum diklaim</span>
                                @endif
                            </td>
                            <td><span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-ink-100 px-2 font-bold tabular-nums text-ink-600 dark:bg-white/7 dark:text-ink-300">{{ $counter->sort_order }}</span></td>
                            <td><span @class(['admin-badge-active' => $counter->is_active, 'admin-badge-inactive' => ! $counter->is_active])>{{ $counter->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $counter->id }})" class="admin-action-btn">Edit</button>
                                    <button type="button" wire:click="delete({{ $counter->id }})" wire:confirm="Hapus loket {{ $counter->name }}?" class="admin-btn-danger">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="admin-empty-state !border-0 !bg-transparent py-12">
                                    <p class="font-semibold text-ink-700 dark:text-ink-200">{{ $search !== '' ? 'Loket tidak ditemukan' : 'Belum ada loket' }}</p>
                                    <p class="mt-1 text-sm text-ink-400">{{ $search !== '' ? 'Coba gunakan kata pencarian lain.' : 'Tambahkan loket pertama agar operator dapat melayani antrean.' }}</p>
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
            :title="$editingId ? 'Edit loket' : 'Tambah loket'"
            description="Atur identitas loket dan tentukan layanan yang dapat diproses oleh operator."
            size="xl"
        >
            <form id="counter-form" wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="counter-code" class="admin-form-label">Kode loket</label>
                    <input id="counter-code" wire:model="code" class="admin-form-input font-display font-bold uppercase" maxlength="10" placeholder="Contoh: L1" autofocus>
                    <p class="admin-form-hint">Kode singkat yang terlihat pada panel dan laporan.</p>
                    @error('code') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="counter-name" class="admin-form-label">Nama loket</label>
                    <input id="counter-name" wire:model="name" class="admin-form-input" placeholder="Contoh: Loket Pendaftaran">
                    <p class="admin-form-hint">Samakan dengan tulisan pada ruang pelayanan.</p>
                    @error('name') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="counter-order" class="admin-form-label">Urutan tampilan</label>
                    <input id="counter-order" type="number" wire:model="sort_order" min="0" max="999" class="admin-form-input">
                    <p class="admin-form-hint">Angka kecil tampil lebih dahulu dalam pilihan operator.</p>
                    @error('sort_order') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div class="admin-field-group">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model="is_active" class="mt-0.5 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm font-bold text-ink-800 dark:text-white">Loket aktif</span>
                            <span class="mt-1 block text-xs leading-5 text-ink-500">Operator dapat memilih dan mengklaim loket ini.</span>
                        </span>
                    </label>
                    @error('is_active') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="admin-form-label">Layanan yang ditangani</legend>
                    <p class="mb-3 text-xs leading-5 text-ink-500">Pilih minimal satu layanan. Operator hanya akan memanggil antrean dari layanan yang dipilih.</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($services as $service)
                            <label wire:key="counter-form-service-{{ $service->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 bg-white px-3.5 py-3 text-sm hover:border-brand-300 hover:bg-brand-50/50 dark:border-white/10 dark:bg-white/3">
                                <input type="checkbox" wire:model="serviceIds" value="{{ $service->id }}" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                                <span class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-brand-100 px-2 font-display text-xs font-bold text-brand-800 dark:bg-brand-500/15 dark:text-brand-300">{{ $service->code }}</span>
                                <span class="font-semibold text-ink-700 dark:text-ink-200">{{ $service->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('serviceIds') <p class="admin-form-error">{{ $message }}</p> @enderror
                    @error('serviceIds.*') <p class="admin-form-error">{{ $message }}</p> @enderror
                </fieldset>
            </form>

            <x-slot:footer>
                <button type="button" wire:click="cancelEdit" class="admin-btn-secondary">Batal</button>
                <button type="submit" form="counter-form" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Simpan Loket</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
