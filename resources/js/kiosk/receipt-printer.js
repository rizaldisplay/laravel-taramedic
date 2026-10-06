/**
 * Kiosk receipt printer controller (Alpine).
 * Uses the same Web Bluetooth flow as the POS BluetoothService / PrinterManager.
 */

import { encodeEscPosReceipt } from './escpos';
import { bluetoothService } from './bluetooth-service';

const STORAGE_KEY = 'kiosk.blePrinter.v2';
const ROLE = 'kiosk';

export function createReceiptPrinterController(config = '') {
    const initialConfig = typeof config === 'object' && config !== null
        ? config
        : { institutionName: config };

    return {
        institutionName: initialConfig.institutionName || '',
        institutionAddress: initialConfig.institutionAddress || '',
        institutionPhone: initialConfig.institutionPhone || '',
        receiptSettings: initialConfig.receiptSettings || {},
        status: 'idle', // idle | pairing | connecting | connected | error
        paperWidth: 58,
        deviceName: '',
        deviceId: '',
        lastError: '',
        menuOpen: false,
        autoReconnectEnabled: true,
        supported: false,
        _device: null,
        _server: null,
        _restoreInProgress: false,
        _reconnectAttempts: 0,

        statusLabel() {
            if (this.status === 'connected') {
                return this.shortName() || 'Terhubung';
            }

            if (this.status === 'pairing') {
                return 'Pilih printer...';
            }

            if (this.status === 'connecting') {
                return 'Menghubungkan...';
            }

            if (this.status === 'error') {
                return this.shortError();
            }

            return this.deviceId ? 'Tidak terhubung' : 'Belum dipasang';
        },

        shortName() {
            const name = String(this.deviceName || '');

            return name.length > 16 ? `${name.slice(0, 14)}…` : name;
        },

        shortError() {
            const message = String(this.lastError || 'Error');

            return message.length > 24 ? `${message.slice(0, 22)}…` : message;
        },

        statusTone() {
            return {
                connected: 'bg-success-500',
                pairing: 'bg-sky-400 animate-pulse',
                connecting: 'bg-orange-400 animate-pulse',
                error: 'bg-error-500',
                idle: 'bg-white/35',
            }[this.status] ?? 'bg-white/35';
        },

        async init() {
            this.supported = bluetoothService.isSupported();
            this.loadPrefs();
            window.kioskReceiptPrinter = this;

            window.addEventListener('kiosk-printer-disconnected', (event) => {
                const device = event.detail?.device;

                if (device && this.deviceId && device.id !== this.deviceId) {
                    return;
                }

                this._server = null;
                this.status = 'idle';
                this.lastError = 'Printer terputus.';
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && this.autoReconnectEnabled) {
                    this.loadSavedDeviceAndConnect({ silent: true });
                }
            });

            await this.loadSavedDeviceAndConnect({ silent: true });
        },

        loadPrefs() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                const legacyId = localStorage.getItem(`printer_device_${ROLE}`);

                if (raw) {
                    const prefs = JSON.parse(raw);
                    this.paperWidth = prefs.paperWidth === 80 ? 80 : 58;
                    this.deviceName = prefs.deviceName ?? '';
                    this.deviceId = prefs.deviceId ?? legacyId ?? '';
                    this.autoReconnectEnabled = prefs.autoReconnectEnabled !== false;

                    return;
                }

                // Compatibility with POS-style key if ever shared on same origin.
                if (legacyId) {
                    this.deviceId = legacyId;
                }
            } catch {
                // Ignore.
            }
        },

        savePrefs() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                paperWidth: this.paperWidth,
                deviceName: this.deviceName,
                deviceId: this.deviceId,
                autoReconnectEnabled: this.autoReconnectEnabled,
            }));

            // Same key pattern as POS PrinterManager for consistency.
            if (this.deviceId) {
                localStorage.setItem(`printer_device_${ROLE}`, this.deviceId);
            } else {
                localStorage.removeItem(`printer_device_${ROLE}`);
            }
        },

        setPaperWidth(width) {
            this.paperWidth = width === 80 ? 80 : 58;
            this.savePrefs();
        },

        toggleAutoReconnect() {
            this.autoReconnectEnabled = ! this.autoReconnectEnabled;
            this.savePrefs();

            if (this.autoReconnectEnabled) {
                this.loadSavedDeviceAndConnect({ silent: true });
            }
        },

        toggleMenu() {
            this.menuOpen = ! this.menuOpen;
        },

        closeMenu() {
            this.menuOpen = false;
        },

        isReady() {
            return Boolean(this._device && this._server?.connected);
        },

        /**
         * Pair printer (user gesture) — mirrors POS setupPrinter().
         */
        async pairPrinter() {
            if (! this.supported) {
                this.fail('Web Bluetooth tidak tersedia. Gunakan Chrome/Edge.');

                return false;
            }

            this.status = 'pairing';
            this.lastError = '';

            try {
                const device = await bluetoothService.requestDevice();
                this._device = device;
                this.deviceName = device.name || 'Thermal Printer';
                this.deviceId = device.id || '';
                this.savePrefs();

                this.status = 'connecting';
                this._server = await bluetoothService.connect(device, 3);
                this._reconnectAttempts = 0;
                this.status = 'connected';
                this.lastError = '';
                this.closeMenu();

                return true;
            } catch (error) {
                if (error?.name === 'NotFoundError') {
                    this.status = 'idle';
                    this.lastError = 'Printer tidak dipilih.';

                    return false;
                }

                this.fail(friendlyError(error));

                return false;
            }
        },

        /**
         * Restore saved device id via getDevices() then connect — mirrors POS loadSavedDevices().
         */
        async loadSavedDeviceAndConnect({ silent = false } = {}) {
            if (this._restoreInProgress) {
                return false;
            }

            if (! this.supported) {
                return false;
            }

            if (! this.deviceId && ! this.autoReconnectEnabled) {
                return false;
            }

            const savedId = this.deviceId || localStorage.getItem(`printer_device_${ROLE}`);

            if (! savedId) {
                return false;
            }

            if (this.isReady()) {
                this.status = 'connected';

                return true;
            }

            this._restoreInProgress = true;

            if (! silent) {
                this.status = 'connecting';
                this.lastError = '';
            } else if (this.status !== 'connected') {
                this.status = 'connecting';
            }

            try {
                const permitted = await bluetoothService.getDevices();
                const device = permitted.find((item) => item.id === savedId);

                if (! device) {
                    this._device = null;
                    this._server = null;
                    this.status = 'idle';
                    this.lastError = 'Izin printer hilang. Pasangkan ulang.';

                    return false;
                }

                this._device = device;
                this.deviceName = device.name || this.deviceName || 'Thermal Printer';
                this.deviceId = device.id;
                this.savePrefs();

                // POS limits aggressive auto-reconnect; keep 1 silent attempt per page load unless manual.
                if (silent && this._reconnectAttempts >= 1) {
                    this.status = 'idle';
                    this.lastError = 'Belum terhubung. Tekan Sambungkan.';

                    return false;
                }

                this._reconnectAttempts += 1;
                this._server = await bluetoothService.connect(device, silent ? 1 : 3);
                this.status = 'connected';
                this.lastError = '';

                return true;
            } catch (error) {
                this._server = null;
                this.status = 'idle';
                this.lastError = silent
                    ? 'Gagal sambung otomatis. Pastikan printer nyala/dekat.'
                    : friendlyError(error);

                if (! silent) {
                    this.status = 'error';
                }

                return false;
            } finally {
                this._restoreInProgress = false;
            }
        },

        async autoReconnect() {
            this._reconnectAttempts = 0;

            return this.loadSavedDeviceAndConnect({ silent: false });
        },

        async ensureConnection() {
            if (this.isReady()) {
                return this._server;
            }

            if (! this._device && this.deviceId) {
                const ok = await this.loadSavedDeviceAndConnect({ silent: false });

                if (! ok) {
                    throw new Error(this.lastError || 'Printer belum siap');
                }

                return this._server;
            }

            if (! this._device) {
                throw new Error('Printer belum dipasangkan');
            }

            this.status = 'connecting';
            this._server = await bluetoothService.connect(this._device, 3);
            this.status = 'connected';
            this.lastError = '';

            return this._server;
        },

        async printTicket(ticket) {
            if (! ticket?.ticket_number) {
                return false;
            }

            try {
                const server = await this.ensureConnection();
                const payload = await encodeEscPosReceipt(ticket, {
                    paperWidth: this.paperWidth,
                    institutionName: this.institutionName,
                    institutionAddress: this.institutionAddress,
                    institutionPhone: this.institutionPhone,
                    receiptSettings: this.receiptSettings,
                });

                await bluetoothService.print(server, payload);
                this.status = 'connected';
                this.lastError = '';

                return true;
            } catch (error) {
                this._server = null;
                this.fail(friendlyError(error));

                return false;
            }
        },

        async testPrint() {
            return this.printTicket({
                ticket_number: 'RPP001',
                service_code: 'T',
                service_name: 'Tes Printer',
                estimated_wait_minutes: 0,
                waiting_count: 0,
                issued_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                queue_date: new Date().toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                }),
            });
        },

        async disconnect() {
            bluetoothService.disconnect(this._device);
            this._server = null;
            this.status = 'idle';
            this.lastError = '';
            this.closeMenu();
        },

        async forgetPrinter() {
            bluetoothService.disconnect(this._device);
            this._device = null;
            this._server = null;
            this.deviceName = '';
            this.deviceId = '';
            this._reconnectAttempts = 0;
            this.lastError = '';
            this.status = 'idle';
            this.savePrefs();
            this.closeMenu();
        },

        fail(message) {
            this.lastError = message;
            this.status = 'error';
        },
    };
}

function friendlyError(error) {
    const name = error?.name ?? '';
    const message = String(error?.message ?? '');

    if (name === 'NotFoundError') {
        return 'Printer tidak ditemukan';
    }

    if (name === 'SecurityError') {
        return 'Izin ditolak — pakai HTTPS/localhost';
    }

    if (name === 'NetworkError' || message.includes('no longer in range') || message.includes('Timeout')) {
        return 'Printer di luar jangkauan / timeout';
    }

    return message || 'Koneksi Bluetooth gagal';
}
