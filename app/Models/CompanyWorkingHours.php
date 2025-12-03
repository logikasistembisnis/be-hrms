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

    public function breaktimes()
    {
        return $this->hasMany(CompanyWorkingBreaktime::class, 'companyworkinghoursid', 'companyworkinghoursid');
    }

    // pastikan breaktimes dihapus ketika working hours dihapus (application-level cascade)
    protected static function booted()
    {
        static::deleting(function ($model) {
            // Hapus breaktimes terkait (tidak memengaruhi company lain)
            $model->breaktimes()->delete();
        });
    }
}