<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $user = Auth::user();
        $default = $user->role->canAccessAdmin()
            ? route('admin.dashboard')
            : route('operator.panel');

        $this->redirectIntended(default: $default, navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div>
    <div class="mb-9">
        <p class="text-xs font-bold tracking-[.16em] text-brand-700 uppercase dark:text-brand-300">Akses internal</p>
        <h1 class="mt-3 text-3xl font-bold tracking-[-.045em] text-ink-950 sm:text-4xl dark:text-white">Selamat datang kembali.</h1>
        <p class="mt-3 text-sm leading-6 text-ink-500 dark:text-ink-400">Masuk menggunakan akun administrator atau operator yang telah terdaftar.</p>
    </div>

    <form wire:submit="login" class="space-y-5" novalidate>
        <div>
            <label for="login-email" class="admin-form-label">Alamat email</label>
            <input
                id="login-email"
                type="email"
                wire:model="email"
                placeholder="nama@instansi.go.id"
                class="admin-form-input min-h-13"
                autofocus
                autocomplete="username"
            >
            @error('email') <p class="mt-2 text-sm font-medium text-error-600" role="alert">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ showPassword: false }">
            <label for="login-password" class="admin-form-label">Kata sandi</label>
            <div class="relative">
                <input
                    id="login-password"
                    :type="showPassword ? 'text' : 'password'"
                    wire:model="password"
                    placeholder="Masukkan kata sandi"
                    class="admin-form-input min-h-13 pr-28"
                    autocomplete="current-password"
                >
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute top-1/2 right-3 -translate-y-1/2 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-50 dark:text-brand-300 dark:hover:bg-brand-500/10"
                    :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                >
                    <span x-text="showPassword ? 'Sembunyikan' : 'Tampilkan'"></span>
                </button>
            </div>
            @error('password') <p class="mt-2 text-sm font-medium text-error-600" role="alert">{{ $message }}</p> @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-ink-600 select-none dark:text-ink-400">
            <input type="checkbox" wire:model="remember" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500 dark:border-ink-700">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="admin-btn-primary min-h-13 w-full text-base" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Masuk ke sistem</span>
            <span wire:loading wire:target="login">Memverifikasi…</span>
        </button>
    </form>

    <p class="mt-8 text-center text-xs leading-5 text-ink-400">Akses hanya untuk petugas berwenang. Aktivitas masuk dilindungi pembatasan percobaan.</p>
</div>
