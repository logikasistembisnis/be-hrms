<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDepartment extends Model
{
    use HasFactory;

    protected $table = 'companydepartment';
    protected $primaryKey = 'companydepartmentid';
    public $timestamps = false;

    protected $fillable = [
        'companydepartmentid',
        'companydivisionid',
        'companyid',
        'departmentname',
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
        'companydivisionid' => 'integer',
        'active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function companydivision()
    {
        return $this->belongsTo(CompanyDivision::class, 'companydivisionid', 'companydivisionid');
    }

    public function unitKerjas() 
    {
        return $this->hasMany(CompanyUnitKerja::class, 'companydepartmentid', 'companydepartmentid');
    }
}