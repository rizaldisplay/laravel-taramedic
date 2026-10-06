<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'operator';

    public string $password = '';

    public string $password_confirmation = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->editingId = 0;
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);
        $this->resetValidation();

        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));
        $isCreate = $this->editingId === 0;
        $user = $isCreate ? null : User::query()->findOrFail($this->editingId);

        if ($isCreate) {
            $this->authorize('create', User::class);
        } else {
            $this->authorize('update', $user);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId ?: null),
            ],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => [
                $isCreate ? 'required' : 'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        if (! $isCreate && $user->id === auth()->id() && $data['role'] !== UserRole::Admin->value) {
            $this->addError('role', 'Anda tidak dapat menurunkan peran akun sendiri.');

            return;
        }

        if (
            ! $isCreate
            && $user->role === UserRole::Admin
            && $data['role'] !== UserRole::Admin->value
            && User::query()->where('role', UserRole::Admin)->whereKeyNot($user->id)->doesntExist()
        ) {
            $this->addError('role', 'Sistem harus memiliki minimal satu administrator.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if ($isCreate) {
            User::query()->create($payload);
            session()->flash('message', 'Pengguna berhasil ditambahkan.');
        } else {
            $user->update($payload);
            session()->flash('message', 'Pengguna berhasil diperbarui.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();
        session()->flash('message', 'Pengguna dihapus.');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->role = UserRole::Operator->value;
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'users' => User::query()
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->get(),
            'roles' => UserRole::cases(),
            'userCount' => User::query()->count(),
            'operatorCount' => User::query()->where('role', UserRole::Operator)->count(),
        ];
    }
};
?>

<div>
    <div class="admin-page-header">
        @include('layouts.partials.page-heading', [
            'title' => 'Kelola Pengguna',
            'subtitle' => 'Akun admin dan operator sistem antrian',
        ])
        <button type="button" wire:click="create" class="admin-btn-primary shrink-0">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Tambah Pengguna
        </button>
    </div>

    @include('partials.admin.flash')

    <x-admin.guide
        title="Atur akses dengan aman"
        description="Buat satu akun untuk setiap petugas agar aktivitas mudah dikenali."
        :items="[
            'Operator hanya mengakses panel pelayanan dan loket.',
            'Administrator dapat mengubah konfigurasi serta melihat laporan.',
            'Gunakan kata sandi berbeda untuk setiap akun petugas.',
        ]"
    />

    <div class="admin-table-wrap">
        <div class="admin-table-toolbar">
            <div class="admin-table-search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari nama atau email petugas..." aria-label="Cari pengguna">
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-ink-500">
                <span class="admin-stat-chip">{{ $operatorCount }} operator</span>
                <span>{{ $userCount }} total akun</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ink-100 text-xs font-bold text-ink-700 dark:bg-white/8 dark:text-white">{{ collect(explode(' ', $user->name))->filter()->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->join('') }}</span>
                                    <div>
                                        <p class="font-bold text-ink-950 dark:text-white">{{ $user->name }}</p>
                                        @if (auth()->id() === $user->id)<p class="mt-0.5 text-[11px] font-semibold text-brand-600 dark:text-brand-300">Akun Anda</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="font-medium">{{ $user->email }}</td>
                            <td>
                                <span @class([
                                    'admin-badge-active' => $user->role === \App\Enums\UserRole::Admin,
                                    'admin-badge-inactive' => $user->role === \App\Enums\UserRole::Operator,
                                ])>
                                    {{ ucfirst($user->role->value) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="admin-action-btn">Edit</button>
                                    @if (auth()->id() !== $user->id)
                                        <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Hapus pengguna {{ $user->name }}?" class="admin-btn-danger">Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="admin-empty-state !border-0 !bg-transparent py-12">
                                    <p class="font-semibold text-ink-700 dark:text-ink-200">Pengguna tidak ditemukan</p>
                                    <p class="mt-1 text-sm text-ink-400">Coba gunakan nama atau alamat email lain.</p>
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
            :title="$editingId ? 'Edit pengguna' : 'Tambah pengguna'"
            description="Berikan akses sesuai tanggung jawab petugas di klinik."
        >
            <form id="user-form" wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="user-name" class="admin-form-label">Nama lengkap</label>
                    <input id="user-name" wire:model="name" class="admin-form-input" autocomplete="name" placeholder="Nama petugas" autofocus>
                    <p class="admin-form-hint">Nama akan terlihat pada panel dan aktivitas pelayanan.</p>
                    @error('name') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="user-email" class="admin-form-label">Alamat email</label>
                    <input id="user-email" type="email" wire:model="email" class="admin-form-input" autocomplete="username" placeholder="petugas@klinik.test">
                    <p class="admin-form-hint">Digunakan untuk masuk ke sistem.</p>
                    @error('email') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="user-role" class="admin-form-label">Peran pengguna</label>
                    <select id="user-role" wire:model="role" class="admin-form-input">
                        @foreach ($roles as $item)
                            <option wire:key="role-{{ $item->value }}" value="{{ $item->value }}">{{ ucfirst($item->value) }}</option>
                        @endforeach
                    </select>
                    <p class="admin-form-hint">Pilih Administrator hanya untuk petugas yang boleh mengubah konfigurasi.</p>
                    @error('role') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="user-password" class="admin-form-label">{{ $editingId ? 'Kata sandi baru' : 'Kata sandi' }}</label>
                    <input id="user-password" type="password" wire:model="password" class="admin-form-input" autocomplete="new-password" placeholder="{{ $editingId ? 'Biarkan kosong jika tetap' : 'Masukkan kata sandi' }}">
                    <p class="admin-form-hint">{{ $editingId ? 'Kosongkan jika tidak ingin mengganti kata sandi.' : 'Gunakan kombinasi yang tidak mudah ditebak.' }}</p>
                    @error('password') <p class="admin-form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="user-password-confirmation" class="admin-form-label">Konfirmasi kata sandi</label>
                    <input id="user-password-confirmation" type="password" wire:model="password_confirmation" class="admin-form-input" autocomplete="new-password" placeholder="Ulangi kata sandi">
                </div>
            </form>

            <x-slot:footer>
                <button type="button" wire:click="cancelEdit" class="admin-btn-secondary">Batal</button>
                <button type="submit" form="user-form" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Simpan Pengguna</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</div>
