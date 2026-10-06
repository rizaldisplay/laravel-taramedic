<div class="relative" @click.outside="closeMenu()">
    <button
        type="button"
        class="kiosk-printer-status"
        @click.stop="toggleMenu()"
        :title="lastError || statusLabel()"
        aria-label="Status printer Bluetooth"
    >
        <span class="kiosk-printer-dot" :class="statusTone()"></span>
        <span class="max-w-[7.5rem] truncate" x-text="statusLabel()"></span>
        <span class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-white/70" x-text="paperWidth + 'mm'"></span>
    </button>

    <div
        x-show="menuOpen"
        x-cloak
        x-transition
        class="kiosk-printer-menu"
        @click.stop
    >
        <p class="px-3 py-1.5 text-[10px] font-semibold tracking-wide text-white/40 uppercase">Printer Bluetooth</p>

        <p class="px-3 py-2 text-[10px] leading-relaxed text-error-300" x-show="!supported">
            Web Bluetooth tidak tersedia. Gunakan Chrome/Edge di HTTPS atau localhost.
        </p>

        <button
            type="button"
            class="kiosk-printer-menu-btn"
            @click="pairPrinter()"
            x-show="supported"
        >
            <span x-text="deviceId ? 'Ganti / Pasangkan Ulang' : 'Pasangkan Printer'"></span>
            <span class="text-brand-300">RPP</span>
        </button>

        <button
            type="button"
            class="kiosk-printer-menu-btn"
            @click="autoReconnect()"
            x-show="supported && deviceId && status !== 'connected'"
        >
            <span>Sambungkan Sekarang</span>
        </button>

        <button
            type="button"
            class="kiosk-printer-menu-btn"
            @click="testPrint()"
            x-show="status === 'connected'"
        >
            <span>Tes Cetak</span>
        </button>

        <div class="my-1 border-t border-white/10"></div>
        <p class="px-3 py-1.5 text-[10px] font-semibold tracking-wide text-white/40 uppercase">Lebar kertas</p>
        <button type="button" class="kiosk-printer-menu-btn" @click="setPaperWidth(58)">
            <span>58 mm</span>
            <span x-show="paperWidth === 58" class="text-brand-300">●</span>
        </button>
        <button type="button" class="kiosk-printer-menu-btn" @click="setPaperWidth(80)">
            <span>80 mm</span>
            <span x-show="paperWidth === 80" class="text-brand-300">●</span>
        </button>

        <div class="my-1 border-t border-white/10"></div>
        <button type="button" class="kiosk-printer-menu-btn" @click="toggleAutoReconnect()" x-show="supported">
            <span>Auto reconnect</span>
            <span class="text-brand-300" x-text="autoReconnectEnabled ? 'ON' : 'OFF'"></span>
        </button>

        <button
            type="button"
            class="kiosk-printer-menu-btn"
            @click="disconnect()"
            x-show="status === 'connected'"
        >
            <span>Putuskan</span>
        </button>
        <button
            type="button"
            class="kiosk-printer-menu-btn text-error-300"
            @click="forgetPrinter()"
            x-show="deviceId || deviceName"
        >
            <span>Lupakan Printer</span>
        </button>

        <p class="px-3 py-2 text-[10px] leading-relaxed text-error-300/90" x-show="lastError" x-text="lastError"></p>
        <p class="px-3 pb-2 text-[10px] leading-relaxed text-white/30">
            Konfigurasi sama seperti POS: service <span class="text-white/50">18f0</span> /
            characteristic <span class="text-white/50">2af1</span> (RPP02 dll).
        </p>
    </div>
</div>
