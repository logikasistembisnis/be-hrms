<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyJobFamily extends Model
{
    use HasFactory;

    protected $table = 'companyjobfamily';
    protected $primaryKey = 'companyjobfamilyid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'jobfamilyname',
        'jobfamilycode',
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
        'active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function subfamily() 
    {
        return $this->hasMany(CompanySubFamily::class, 'companyjobfamilyid', 'companyjobfamilyid');
    }
}