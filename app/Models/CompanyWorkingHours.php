<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyWorkingHours extends Model
{
    use HasFactory;

    protected $table = 'companyworkinghours';
    protected $primaryKey = 'companyworkinghoursid';
    public $timestamps = false;

    protected $fillable = [
        'companyworkinghoursid',
        'tipejadwal',
        'kategori',
        'skema',
        'durasi',
        'durasiistirahat',
        'jammasuk',
        'jamkeluar',
        'kodeshift',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'durasi' => 'integer',
        'durasiistirahat' => 'integer',
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
    ];
}