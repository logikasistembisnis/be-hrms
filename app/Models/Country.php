<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'countryid';
    public $timestamps = false;

    protected $fillable = [
        'countryid',
        'name',
        'isocode',
        'shortcode',
        'sysrowid',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}