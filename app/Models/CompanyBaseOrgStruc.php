<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBaseOrgStruc extends Model
{
    use HasFactory;

    protected $table = 'companybaseorgstruc';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'companyid',
        'baseorgstructureid',
        'selected',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyid' => 'integer',
        'baseorgstructureid' => 'integer',
        'selected' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function baseorgstructure()
    {
        return $this->belongsTo(BaseOrgStructure::class,'baseorgstructureid', 'baseorgstructureid');
    }
}