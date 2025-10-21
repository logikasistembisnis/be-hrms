<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = 'company';
    protected $primaryKey = 'companyid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'name',
        'brandname',
        'entitytype',
        'noindukberusaha',
        'npwp',
        'address',
        'telpno',
        'companyemail',
        'logo',
        'holdingflag',
        'desainperusahaan',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
        'countryid',
    ];

    protected $casts = [
        'holdingflag' => 'boolean',
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'countryid' => 'integer'
    ];

    protected $hidden = ['logo'];

    public function country()
    {
        return $this->belongsTo(Country::class, 'countryid', 'countryid');
    }
}