<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarCuti extends Model
{
    protected $table = 'daftarcuti';
    protected $primaryKey = 'daftarcutiid';
    public $timestamps = false;

    protected $fillable = [
        'deskripsi',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}