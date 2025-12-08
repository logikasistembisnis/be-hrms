<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySubFamily extends Model
{
    use HasFactory;

    protected $table = 'companysubfamily';
    protected $primaryKey = 'companysubfamilyid';
    public $timestamps = false;

    protected $fillable = [
        'companyjobfamilyid',
        'companyid',
        'subfamilyname',
        'subfamilycode',
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
        'companyjobfamilyid' => 'integer',
        'active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function jobfamily()
    {
        return $this->belongsTo(CompanyJobFamily::class, 'companyjobfamilyid', 'companyjobfamilyid');
    }
}