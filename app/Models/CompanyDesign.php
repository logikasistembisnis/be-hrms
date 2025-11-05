<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDesign extends Model
{
    protected $table = 'companydesign';
    protected $primaryKey = 'companydesignid';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'level',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];
}