<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerScheduleSlot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'jenis_pasien' => 'array',
        'berlaku_mulai' => 'date',
        'berlaku_hingga' => 'date',
    ];
}
