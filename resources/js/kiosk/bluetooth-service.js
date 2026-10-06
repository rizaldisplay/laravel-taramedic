/**
 * Low-level Web Bluetooth for thermal ESC/POS printers.
 * Ported from the proven POS BluetoothService (RPP02 / Feasycom 18f0 + 2af1).
 */

export const BLE_SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
export const BLE_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';

const DEFAULT_FILTERS = [
    { namePrefix: 'RPP' },
    { namePrefix: 'Thermal' },
    { namePrefix: 'POS' },
    { namePrefix: 'BlueTooth' },
    { namePrefix: 'Printer' },
];

export class BluetoothService {
    constructor() {
        this.serviceUUID = BLE_SERVICE_UUID;
        this.characteristicUUID = BLE_CHARACTERISTIC_UUID;
        this.chunkSize = 180;
        this.chunkDelayMs = 0;
        this._disconnectHandler = null;
    }

    isSupported() {
        return Boolean(navigator.bluetooth);
    }

    /**
     * Must be triggered by a user gesture.
     */
    async requestDevice() {
        if (! navigator.bluetooth) {
            throw new Error('Web Bluetooth tidak didukung. Gunakan Chrome/Edge.');
        }

        return navigator.bluetooth.requestDevice({
            filters: DEFAULT_FILTERS,
            optionalServices: [this.serviceUUID],
        });
    }

    async getDevices() {
        if (! navigator.bluetooth?.getDevices) {
            return [];
        }

        try {
            return await navigator.bluetooth.getDevices();
        } catch {
            return [];
        }
    }

    /**
     * Connect GATT with advertisement watch, timeout, and retries.
     */
    async connect(device, retries = 3) {
        if (! device) {
            throw new Error('No device provided');
        }

        if (device.gatt?.connected) {
            return device.gatt;
        }

        let attempt = 0;
        let lastError = null;

        while (attempt < retries) {
            attempt += 1;

            try {
                return await this._connectWithAdvertisement(device);
            } catch (error) {
                lastError = error;

                if (attempt >= retries) {
                    break;
                }

                await sleep(1000 * (2 ** (attempt - 1)));
            }
        }

        throw lastError ?? new Error('Gagal koneksi Bluetooth');
    }

    async _connectWithAdvertisement(device) {
        return new Promise(async (resolve, reject) => {
            const abortController = new AbortController();
            let timer = null;
            let isHandled = false;

            const cleanup = () => {
                if (timer) {
                    clearTimeout(timer);
                }

                device.removeEventListener('advertisementreceived', handleAdvertisement);

                if (device.watchingAdvertisements && typeof device.unwatchAdvertisements === 'function') {
                    device.unwatchAdvertisements().catch(() => {});
                }
            };

            const finishConnect = async () => {
                const server = await device.gatt.connect();
                await server.getPrimaryService(this.serviceUUID);
                this._attachDisconnectListener(device);

                return server;
            };

            const handleAdvertisement = async () => {
                if (isHandled) {
                    return;
                }

                isHandled = true;
                cleanup();

                try {
                    resolve(await finishConnect());
                } catch (error) {
                    reject(error);
                }
            };

            timer = setTimeout(() => {
                if (isHandled) {
                    return;
                }

                isHandled = true;
                abortController.abort();
                cleanup();
                reject(new Error('NetworkError: Bluetooth Device is no longer in range (Timeout)'));
            }, 10000);

            try {
                if (typeof device.watchAdvertisements === 'function') {
                    device.addEventListener('advertisementreceived', handleAdvertisement);
                    await device.watchAdvertisements({ signal: abortController.signal });
                } else {
                    cleanup();
                    resolve(await finishConnect());
                }
            } catch (error) {
                if (isHandled) {
                    return;
                }

                isHandled = true;
                cleanup();

                try {
                    resolve(await finishConnect());
                } catch (directError) {
                    reject(directError ?? error);
                }
            }
        });
    }

    _attachDisconnectListener(device) {
        if (this._disconnectHandler) {
            device.removeEventListener('gattserverdisconnected', this._disconnectHandler);
        }

        this._disconnectHandler = (event) => {
            window.dispatchEvent(new CustomEvent('kiosk-printer-disconnected', {
                detail: { device: event.target },
            }));
        };

        device.addEventListener('gattserverdisconnected', this._disconnectHandler);
    }

    /**
     * Send ESC/POS bytes using fixed Feasycom characteristic (same as POS).
     */
    async print(server, data) {
        if (! server?.connected) {
            throw new Error('Printer not connected');
        }

        const service = await server.getPrimaryService(this.serviceUUID);
        const characteristic = await service.getCharacteristic(this.characteristicUUID);

        await this.sendChunks(characteristic, data);
    }

    async sendChunks(characteristic, data) {
        let offset = 0;
        const delayMs = data && data.length > 4096 ? 10 : this.chunkDelayMs;

        while (offset < data.length) {
            const chunk = data.slice(offset, offset + this.chunkSize);
            await characteristic.writeValue(chunk);
            offset += this.chunkSize;

            if (delayMs > 0) {
                await sleep(delayMs);
            }
        }
    }

    disconnect(device) {
        try {
            if (device?.gatt?.connected) {
                device.gatt.disconnect();
            }
        } catch {
            // Ignore.
        }
    }
}

export const bluetoothService = new BluetoothService();

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
