<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerScheduleDay extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['aktif' => 'boolean', 'jenis_pasien' => 'array'];
}
