<?php

namespace App\Events;

use App\Models\Patient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientUpdated
{
    use Dispatchable, SerializesModels;

    /** @param list<string> $changedFields nama kolom yang berubah (tanpa nilainya) */
    public function __construct(
        public readonly Patient $patient,
        public readonly array $changedFields,
        public readonly ?int $updatedBy = null,
    ) {
    }
}
