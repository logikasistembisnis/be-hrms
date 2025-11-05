<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HRBaseRule extends Model
{
    protected $table = 'hrbaserule';
    protected $primaryKey = 'hrbaserule';
    public $timestamps = false;

    protected $fillable = [
        'grouprule',
        'rulename',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}