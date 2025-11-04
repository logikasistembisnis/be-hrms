<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseOrgStructure extends Model
{
    protected $table = 'baseorgstructure';
    protected $primaryKey = 'baseorgstructureid';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'ordeno',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}