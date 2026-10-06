<div>
    <div class="mb-6">
        <h2 class="font-display text-lg font-bold tracking-[-.025em] text-ink-950 dark:text-white">Informasi Instansi</h2>
        <p class="mt-1 text-sm text-gray-500">Ditampilkan di kios, monitor, dan halaman login</p>
    </div>

    <form wire:submit="saveInstitution" class="space-y-6">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="institution-name" class="admin-form-label">Nama instansi</label>
                <input id="institution-name" wire:model="institution_name" class="admin-form-input" placeholder="Contoh: Klinik Pratama Mitra Sehat">
                <p class="admin-form-hint">Gunakan nama resmi yang dikenal pasien.</p>
                @error('institution_name') <p class="admin-form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="institution-tagline" class="admin-form-label">Tagline</label>
                <input id="institution-tagline" wire:model="institution_tagline" class="admin-form-input" placeholder="Pelayanan mudah dan tertib">
                <p class="admin-form-hint">Satu kalimat pendek yang mewakili layanan klinik.</p>
            </div>
            <div class="md:col-span-2">
                <label for="institution-address" class="admin-form-label">Alamat</label>
                <input id="institution-address" wire:model="institution_address" class="admin-form-input" placeholder="Jl. Contoh No. 1, Kota">
            </div>
            <div>
                <label for="institution-phone" class="admin-form-label">Telepon</label>
                <input id="institution-phone" wire:model="institution_phone" class="admin-form-input" placeholder="(021) 555-0123">
            </div>
            <div class="md:col-span-2">
                <label for="institution-logo" class="admin-form-label">Logo institusi</label>
                <div class="grid gap-4 rounded-2xl border border-ink-200 bg-ink-50/70 p-4 md:grid-cols-[auto_1fr] dark:border-white/8 dark:bg-white/3">
                    <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border border-ink-200 bg-white dark:border-white/10 dark:bg-ink-950">
                        @if ($institution_logo && ! empty($institution_logo['url']))
                            <img src="{{ $institution_logo['url'] }}" alt="Logo {{ $institution_name }}" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-xs font-semibold text-ink-400">Belum ada logo</span>
                        @endif
                    </div>
                    <div class="min-w-0 space-y-3">
                        <input id="institution-logo" type="file" wire:model="institution_logo_upload" accept="image/png,image/jpeg,image/webp" class="admin-form-input">
                        <p class="admin-form-hint">Format PNG, JPG, atau WEBP. Ukuran maksimal 2MB. Logo ini dipakai di sidebar, display TV, kiosk, dan struk jika diaktifkan.</p>
                        @error('institution_logo_upload') <p class="admin-form-error">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="institution_logo_upload,saveInstitutionLogo" class="text-sm font-medium text-brand-700 dark:text-brand-300">Mengunggah logo...</div>
                        @if ($institution_logo)
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="truncate text-sm text-ink-500 dark:text-ink-300">{{ $institution_logo['name'] }}</span>
                                <button type="button" wire:click="removeInstitutionLogo" wire:loading.attr="disabled" wire:target="removeInstitutionLogo" class="admin-btn-danger">Hapus logo</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <label for="kiosk-message" class="admin-form-label">Pesan selamat datang di kiosk</label>
                <textarea id="kiosk-message" wire:model="kiosk_welcome_message" rows="3" class="admin-form-input" placeholder="Selamat datang. Silakan pilih layanan..."></textarea>
                <p class="admin-form-hint">Tulis arahan singkat yang langsung menjelaskan tindakan pasien.</p>
            </div>
        </div>
        <div class="flex justify-end border-t border-gray-100 pt-6 dark:border-gray-800">
            <button type="submit" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="saveInstitution">
                <span wire:loading.remove wire:target="saveInstitution">Simpan Informasi Instansi</span>
                <span wire:loading wire:target="saveInstitution">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
