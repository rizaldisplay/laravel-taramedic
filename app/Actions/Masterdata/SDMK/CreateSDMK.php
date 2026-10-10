<?php

namespace App\Actions\Masterdata\SDMK;

use App\DTO\Masterdata\SDMKData;
use App\DTO\Masterdata;
use App\Events\SDMKRegistered;
use App\Models\Sdmk;
use Illuminate\Support\Facades\DB;

final class CreateSDMK{
    public function __construct(
        private readonly GenerateSDMKdNumber $generateMedicalRecordNumber,
        private readonly GenerateSIPNumber $generateSIPNumber,
        private readonly GenerateSTRNumber $generateSTRNumber,
    ) {}

}