<?php

namespace App\Http\Requests;

use App\Models\Patient;

class UpdatePatientRequest extends PatientFormRequest
{
    // public function authorize(): bool
    // {
    //     $patient = $this->route('patient');

    //     return $patient instanceof Patient && ($this->user()?->can('update', $patient) ?? false);
    // }

    protected function patientId(): ?int
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient ? $patient->id : null;
    }
}
