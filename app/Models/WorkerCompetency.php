<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerCompetency extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['document_path'];

    protected $casts = ['tanggal_peroleh' => 'date', 'berlaku_hingga' => 'date'];
}
