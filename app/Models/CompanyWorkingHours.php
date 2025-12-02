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
        'companyid',
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
        'companyid' => 'integer',
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }
}