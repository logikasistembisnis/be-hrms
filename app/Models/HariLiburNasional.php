<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLiburNasional extends Model
{
    protected $table = 'hariliburnasional';
    protected $primaryKey = 'hariliburnasid';
    public $timestamps = false;

    protected $fillable = [
        'startdate',
        'enddate',
        'namatanggal',
        'active',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}