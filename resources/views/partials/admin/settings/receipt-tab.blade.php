@php
    $visibilityOptions = [
        'show_institution_name' => 'Nama institusi',
        'show_institution_address' => 'Alamat',
        'show_institution_phone' => 'Telepon',
        'show_receipt_title' => 'Judul struk',
        'show_ticket_label' => 'Label nomor antrean',
        'show_service_name' => 'Nama layanan',
        'show_service_code' => 'Kode layanan',
        'show_estimated_wait' => 'Estimasi tunggu',
        'show_waiting_count' => 'Jumlah antrean',
        'show_queue_date' => 'Tanggal',
        'show_issued_time' => 'Jam cetak',
        'show_footer' => 'Catatan bawah',
    ];

    $labelFields = [
        'receipt_title_label' => ['Judul struk', 'TIKET ANTRIAN'],
        'ticket_number_label' => ['Label nomor antrean', 'NOMOR ANTRIAN'],
        'service_name_label' => ['Nama layanan', 'Layanan'],
        'service_code_label' => ['Kode layanan', 'Kode'],
        'estimated_wait_label' => ['Estimasi', 'Estimasi'],
        'waiting_count_label' => ['Jumlah antrean', 'Antrian'],
        'queue_date_label' => ['Tanggal', 'Tanggal'],
        'issued_time_label' => ['Jam cetak', 'Jam'],
        'estimated_wait_suffix' => ['Satuan estimasi', 'menit'],
        'waiting_count_suffix' => ['Satuan jumlah antrean', 'orang'],
        'footer_line_1' => ['Catatan bawah baris 1', 'Harap menunggu panggilan'],
        'footer_line_2' => ['Catatan bawah baris 2', 'di monitor ruang tunggu'],
        'thank_you_label' => ['Ucapan terima kasih', 'Terima kasih'],
    ];
@endphp

<div>
    <div class="mb-6">
        <h2 class="font-display text-lg font-bold tracking-[-.025em] text-ink-950 dark:text-white">Konfigurasi Cetak Struk</h2>
        <p class="mt-1 text-sm text-gray-500">Atur informasi yang muncul pada tiket thermal kiosk</p>
    </div>

    <form wire:submit="saveReceiptSettings" class="space-y-7">
        <section class="rounded-2xl border border-ink-200 bg-ink-50/70 p-4 dark:border-white/8 dark:bg-white/3">
            <h3 class="text-sm font-bold text-ink-900 dark:text-white">Informasi yang dicetak</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($visibilityOptions as $key => $label)
                    <label wire:key="receipt-toggle-{{ $key }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 bg-white px-3.5 py-3 text-sm font-semibold text-ink-700 hover:border-brand-300 dark:border-white/8 dark:bg-white/4 dark:text-ink-200">
                        <input type="checkbox" wire:model="receipt.{{ $key }}" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold text-ink-900 dark:text-white">Judul dan label informasi</h3>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                @foreach ($labelFields as $key => [$label, $placeholder])
                    <div wire:key="receipt-label-{{ $key }}">
                        <label for="receipt-{{ str($key)->kebab() }}" class="admin-form-label">{{ $label }}</label>
                        <input
                            id="receipt-{{ str($key)->kebab() }}"
                            wire:model="receipt.{{ $key }}"
                            class="admin-form-input"
                            placeholder="{{ $placeholder }}"
                        >
                        @error('receipt.'.$key) <p class="admin-form-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="rounded-2xl border border-brand-200 bg-brand-50/70 p-4 text-sm text-brand-900 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-100">
            Perubahan ini berlaku untuk cetak otomatis dan tombol Cetak Lagi pada kiosk. Untuk jumlah antrean, atur judulnya pada field <span class="font-bold">Jumlah antrean</span> dan satuannya pada field <span class="font-bold">Satuan jumlah antrean</span>.
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-6 dark:border-gray-800">
            <button type="submit" class="admin-btn-primary" wire:loading.attr="disabled" wire:target="saveReceiptSettings">
                <span wire:loading.remove wire:target="saveReceiptSettings">Simpan Pengaturan Struk</span>
                <span wire:loading wire:target="saveReceiptSettings">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
