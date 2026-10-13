<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Models\HealthWorker;
use Illuminate\Console\Command;

class LinkDoctorsFromCsv extends Command
{
    protected $signature = 'doctors:link-csv {file : CSV dengan header doctor_id,sdmk_code} {--dry-run : Periksa saja, jangan simpan}';

    protected $description = 'Petakan dokter lama (doctors.id) ke SDMK (sdmk_code). Tidak pernah mencocokkan berdasarkan nama.';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_readable($path) || ($fh = fopen($path, 'r')) === false) {
            $this->error('File tidak dapat dibaca.');

            return self::FAILURE;
        }

        $header = array_map('trim', fgetcsv($fh) ?: []);
        if ($header !== ['doctor_id', 'sdmk_code']) {
            $this->error('Header harus persis: doctor_id,sdmk_code');

            return self::FAILURE;
        }

        $ok = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            if (count($row) < 2 || trim(implode('', $row)) === '') {
                continue;
            }
            [$doctorId, $code] = [trim($row[0]), trim($row[1])];

            $doctor = ctype_digit($doctorId) ? Doctor::find((int) $doctorId) : null;
            $worker = HealthWorker::where('sdmk_code', $code)->first();

            if (! $doctor) {
                $errors[] = "Baris {$line}: dokter #{$doctorId} tidak ditemukan.";
            } elseif (! $worker) {
                $errors[] = "Baris {$line}: SDMK {$code} tidak ditemukan.";
            } elseif ($doctor->health_worker_id && $doctor->health_worker_id !== $worker->id) {
                $errors[] = "Baris {$line}: dokter #{$doctorId} sudah terhubung ke SDMK lain.";
            } elseif (Doctor::where('health_worker_id', $worker->id)->where('poli_id', $doctor->poli_id)->where('id', '!=', $doctor->id)->exists()) {
                $errors[] = "Baris {$line}: SDMK {$code} sudah punya baris dokter di poli yang sama.";
            } else {
                if (! $this->option('dry-run')) {
                    $doctor->update(['health_worker_id' => $worker->id, 'name' => $worker->displayName()]);
                }
                $ok++;
            }
        }
        fclose($fh);

        foreach ($errors as $e) {
            $this->warn($e);
        }
        $this->info(($this->option('dry-run') ? '[DRY RUN] ' : '') . "{$ok} dokter dipetakan, " . count($errors) . ' baris ditolak.');

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
