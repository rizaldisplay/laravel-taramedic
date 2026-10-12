<?php

namespace App\Actions\Masterdata\Sdmk;

use App\Enums\StatusIhs;
use App\Events\SdmkActivityRecorded;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\HealthWorker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Mencari Practitioner di SATUSEHAT berdasarkan NIK untuk mendapatkan IHS Number.
 * BELUM DIUJI terhadap server SATUSEHAT sungguhan (tidak ada kredensial di lingkungan
 * pengembangan). Cek ulang bentuk respons di sandbox Kemenkes sebelum dipakai produksi.
 * NIK dikirim ke SATUSEHAT tetapi TIDAK PERNAH ditulis ke log lokal.
 */
final class VerifyIhsAction
{
    public function execute(HealthWorker $worker, ?int $userId): HealthWorker
    {
        $cfg = config('satusehat');

        if (! $cfg['client_id'] || ! $cfg['client_secret'] || ! $cfg['organization_id']) {
            throw new IntegrationNotConfiguredException();
        }

        try {
            $token = Http::asForm()->timeout($cfg['timeout'])
                ->post($cfg['auth_url'] . '/accesstoken?grant_type=client_credentials', [
                    'client_id' => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                ])->throw()->json('access_token');

            $bundle = Http::withToken((string) $token)->timeout($cfg['timeout'])
                ->get($cfg['base_url'] . '/Practitioner', [
                    'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $worker->nik,
                ])->throw()->json();
        } catch (ConnectionException) {
            return $this->fail($worker, 'Tidak dapat terhubung ke SATUSEHAT', $userId, throw: new IntegrationNotConfiguredException('SATUSEHAT tidak dapat dihubungi. Coba lagi nanti.'));
        } catch (Throwable $e) {
            return $this->fail($worker, 'SATUSEHAT menolak permintaan (HTTP ' . ($e->getCode() ?: 'error') . ')', $userId, throw: new IntegrationNotConfiguredException('Verifikasi IHS gagal. Periksa konfigurasi atau coba lagi nanti.'));
        }

        $ihs = $bundle['entry'][0]['resource']['id'] ?? null;

        if (! $ihs) {
            $worker->forceFill(['ihs_status' => StatusIhs::Gagal])->save();
            $worker->apiLogs()->create(['aksi' => 'Verifikasi IHS', 'status' => 'Gagal', 'pesan' => 'Practitioner tidak ditemukan di SATUSEHAT']);

            return $worker;
        }

        $worker->forceFill([
            'ihs_status' => StatusIhs::Terhubung,
            'ihs_number' => $ihs,
            'organization_id' => $cfg['organization_id'],
            'ihs_synced_at' => now(),
        ])->save();

        $worker->apiLogs()->create(['aksi' => 'Verifikasi IHS', 'status' => 'Berhasil', 'pesan' => 'IHS Number ditemukan']);
        SdmkActivityRecorded::dispatch($worker->id, 'audit', 'Verifikasi IHS berhasil', $worker->sdmk_code, $userId);

        return $worker;
    }

    private function fail(HealthWorker $worker, string $pesan, ?int $userId, Throwable $throw): never
    {
        $worker->forceFill(['ihs_status' => StatusIhs::Gagal])->save();
        $worker->apiLogs()->create(['aksi' => 'Verifikasi IHS', 'status' => 'Gagal', 'pesan' => $pesan]);
        SdmkActivityRecorded::dispatch($worker->id, 'audit', 'Verifikasi IHS gagal', $worker->sdmk_code, $userId);

        throw $throw;
    }
}
