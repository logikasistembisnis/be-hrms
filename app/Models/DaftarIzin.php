<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarIzin extends Model
{
    protected $table = 'daftarizin';
    protected $primaryKey = 'daftarizinid';
    public $timestamps = false;

    protected $fillable = [
        'deskripsi',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}