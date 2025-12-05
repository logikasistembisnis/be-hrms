<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUnitKerja extends Model
{
    use HasFactory;

    protected $table = 'companyunitkerja';
    protected $primaryKey = 'companyunitkerjaid';
    public $timestamps = false;

    protected $fillable = [
        'companyunitkerjaid',
        'companydepartmentid',
        'companyid',
        'unitkerjaname',
        'dokumenname',
        'dokumenfilename',
        'active',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyid' => 'integer',
        'companydepartmentid' => 'integer',
        'active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function companydepartment()
    {
        return $this->belongsTo(CompanyDepartment::class, 'companydepartmentid', 'companydepartmentid');
    }
}