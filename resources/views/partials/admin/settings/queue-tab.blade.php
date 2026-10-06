<div class="max-w-3xl">
    <div class="mb-6">
        <h2 class="font-display text-lg font-bold tracking-[-.025em] text-ink-950 dark:text-white">Konfigurasi Antrian</h2>
        <p class="mt-1 text-sm text-gray-500">Perilaku kios dan monitor display</p>
    </div>

    <form wire:submit="saveQueueSettings" class="space-y-5">
        <div>
            <label for="display-recent-limit" class="admin-form-label">Jumlah riwayat panggilan di monitor</label>
            <input id="display-recent-limit" type="number" wire:model="display_recent_limit" class="admin-form-input" min="1" max="8">
            <p class="admin-form-hint">Jumlah kartu panggilan sebelumnya pada bagian bawah monitor.</p>
            @error('display_recent_limit') <p class="admin-form-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="kiosk-success-reset" class="admin-form-label">Kembali setelah tiket terbit</label>
                <input id="kiosk-success-reset" type="number" wire:model="kiosk_success_reset_seconds" class="admin-form-input" min="5" max="120">
                <p class="admin-form-hint">Detik sebelum kiosk kembali ke layar awal.</p>
                @error('kiosk_success_reset_seconds') <p class="admin-form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="kiosk-idle-reset" class="admin-form-label">Batas kiosk tidak digunakan</label>
                <input id="kiosk-idle-reset" type="number" wire:model="kiosk_idle_reset_seconds" class="admin-form-input" min="15" max="300">
                <p class="admin-form-hint">Detik tanpa sentuhan sebelum sesi dibersihkan.</p>
                @error('kiosk_idle_reset_seconds') <p class="admin-form-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="called-no-show" class="admin-form-label">Batas otomatis tidak hadir</label>
            <input id="called-no-show" type="number" wire:model="called_no_show_after_minutes" class="admin-form-input" min="0" max="120">
            <p class="admin-form-hint">Dalam menit. Antrean terpanggil yang belum dilayani akan ditandai tidak hadir. Isi 0 untuk menonaktifkan.</p>
            @error('called_no_show_after_minutes') <p class="admin-form-error">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end border-t border-gray-100 pt-5 dark:border-gray-800">
            <button type="submit" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="saveQueueSettings">
                <span wire:loading.remove wire:target="saveQueueSettings">Simpan Pengaturan Antrian</span>
                <span wire:loading wire:target="saveQueueSettings">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
