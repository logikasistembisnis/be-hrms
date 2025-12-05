<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDivision extends Model
{
    use HasFactory;

    protected $table = 'companydivision';
    protected $primaryKey = 'companydivisionid';
    public $timestamps = false;

    protected $fillable = [
        'companydivisionid',
        'companydirectorateid',
        'companyid',
        'divisionname',
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
        'companydirectorateid' => 'integer',
        'active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function companydirectorate()
    {
        return $this->belongsTo(CompanyDirectorate::class, 'companydirectorateid', 'companydirectorateid');
    }

    public function departments() 
    {
        return $this->hasMany(CompanyDepartment::class, 'companydivisionid', 'companydivisionid');
    }
}