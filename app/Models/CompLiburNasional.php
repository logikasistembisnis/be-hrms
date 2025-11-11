<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompLiburNasional extends Model
{
    use HasFactory;

    protected $table = 'compliburnasional';
    protected $primaryKey = 'compliburnasionalid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'hariliburnasid',
        'startdate',
        'enddate',
        'namatanggal',
        'potongcutitahunan',
        'dokumenfilename',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyid' => 'integer',
        'hariliburnasid' => 'integer',
        'startdate' => 'date',
        'enddate' => 'date',
        'potongcutitahunan' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function hariliburnasional()
    {
        return $this->belongsTo(HariLiburNasional::class,'hariliburnasid', 'hariliburnasid')
            ->where('hariliburnasid', '>', 0); //tidak ambil data relasi kalau hariliburnasid = 0
    }
}