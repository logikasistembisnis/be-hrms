<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyIzin extends Model
{
    use HasFactory;

    protected $table = 'companyizin';
    protected $primaryKey = 'companyizinid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'daftarizinid',
        'deskripsi',
        'jumlahhari',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyid' => 'integer',
        'daftarizinid' => 'integer',
        'jumlahhari' => 'integer'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function daftarizin()
    {
        return $this->belongsTo(DaftarIzin::class,'daftarizinid', 'daftarizinid')
            ->where('daftarizinid', '>', 0); //tidak ambil data relasi kalau daftarizinid = 0
    }
}