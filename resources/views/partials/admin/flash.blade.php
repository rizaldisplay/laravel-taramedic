@if (session('message') || session('error'))
    <div class="admin-toast-viewport" aria-live="polite">
        @if (session('message'))
            <div x-data="adminToast()" x-show="visible" x-cloak x-transition.opacity.scale.95 class="admin-toast admin-toast-success" role="status">
                <span class="admin-toast-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="admin-toast-title">Berhasil</span>
                    <span class="admin-toast-message">{{ session('message') }}</span>
                </span>
                <button type="button" class="admin-toast-close" @click="close()" aria-label="Tutup notifikasi">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <span class="admin-toast-progress" aria-hidden="true"></span>
            </div>
        @endif

        @if (session('error'))
            <div x-data="adminToast()" x-show="visible" x-cloak x-transition.opacity.scale.95 class="admin-toast admin-toast-error" role="alert">
                <span class="admin-toast-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="admin-toast-title">Perlu diperiksa</span>
                    <span class="admin-toast-message">{{ session('error') }}</span>
                </span>
                <button type="button" class="admin-toast-close" @click="close()" aria-label="Tutup notifikasi">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <span class="admin-toast-progress" aria-hidden="true"></span>
            </div>
        @endif
    </div>
@endif
