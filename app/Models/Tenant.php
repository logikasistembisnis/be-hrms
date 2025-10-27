<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenant';
    protected $primaryKey = 'tenantid';
    public $timestamps = false;

    protected $fillable = [
        'tenantid',
        'name',
        'holdingflag',
        'holdingcompanyid',
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

    public function tenant()
    {
        return $this->hasMany(Company::class, 'tenantid', 'tenantid');
    }
    
    public function holdingCompany()
    {
        return $this->belongsTo(Company::class, 'holdingcompanyid', 'companyid');
    }
}