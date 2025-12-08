<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenant';
    protected $primaryKey = 'tenantid';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'holdingflag',
        'holdingcompanyid',
        'active',
        'sysrowid',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'holdingflag' => 'boolean',
        'holdingcompanyid' => 'integer',
    ];

    public function holdingCompany()
    {
        return $this->belongsTo(Company::class, 'holdingcompanyid', 'companyid');
    }
}