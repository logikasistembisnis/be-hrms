<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCuti extends Model
{
    use HasFactory;

    protected $table = 'companycuti';
    protected $primaryKey = 'companycutiid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'daftarcutiid',
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
        'daftarcutiid' => 'integer',
        'jumlahhari' => 'integer'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function daftarcuti()
    {
        return $this->belongsTo(DaftarCuti::class,'daftarcutiid', 'daftarcutiid')
            ->where('daftarcutiid', '>', 0); //tidak ambil data relasi kalau daftarcutiid = 0
    }
}