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
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
        'countryid',
        'companydesignid',
        'reporttocompanyid',
        'tenantid',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'countryid' => 'integer',
        'companydesignid' => 'integer',
        'reporttocompanyid' => 'integer',
        'tenantid' => 'integer'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'countryid', 'countryid');
    }

    public function companydesign()
    {
        return $this->belongsTo(CompanyDesign::class,'companydesignid', 'companydesignid');
    }

    public function reporttocompany()
    {
        return $this->belongsTo(Company::class,'reporttocompanyid', 'companyid');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenantid', 'tenantid');
    }

    public function companybaseorgstruc()
    {
        return $this->hasMany(CompanyBaseOrgStruc::class, 'companyid', 'companyid');
    }
}