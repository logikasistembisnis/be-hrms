<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyJobRepository extends Model
{
    use HasFactory;

    protected $table = 'companyjobrepository';
    protected $primaryKey = 'companyjobrepositoryid';
    public $timestamps = false;

    protected $fillable = [
        'companyjobrepositoryid',
        'companyid',
        'companydirectorateid',
        'companydivisionid', 
        'companydepartmentid', 
        'companyunitkerjaid',
        'companyjobfamilyid',
        'companysubfamilyid',
        'kodeposisi',
        'namaposisi',
        'reporttoid',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyid' => 'integer',
        'companydirectorateid' => 'integer',
        'companydivisionid' => 'integer',
        'companydepartmentid' => 'integer',
        'companyunitkerjaid' => 'integer',
        'companyjobfamilyid' => 'integer',
        'companysubfamilyid' => 'integer',
        'reporttoid' => 'integer'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function companydirectorate()
    {
        return $this->belongsTo(CompanyDirectorate::class,'companydirectorateid', 'companydirectorateid');
    }

    public function companydivision()
    {
        return $this->belongsTo(CompanyDivision::class,'companydivisionid', 'companydivisionid');
    }

    public function companydepartment()
    {
        return $this->belongsTo(CompanyDepartment::class, 'companydepartmentid', 'companydepartmentid');
    }

    public function companyunitkerja()
    {
        return $this->belongsTo(CompanyUnitKerja::class, 'companyunitkerjaid', 'companyunitkerjaid');
    }

    public function companyjobfamily()
    {
        return $this->belongsTo(CompanyJobFamily::class, 'companyjobfamilyid', 'companyjobfamilyid');
    }

    public function companysubfamily()
    {
        return $this->belongsTo(CompanySubFamily::class, 'companysubfamilyid', 'companysubfamilyid');
    }

    public function reportto()
    {
        return $this->belongsTo(CompanyJobRepository::class, 'companyjobrepositoryid', 'companyjobrepositoryid');
    }
}