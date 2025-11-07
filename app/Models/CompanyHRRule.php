<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyHRRule extends Model
{
    use HasFactory;

    protected $table = 'companyhrrule';
    protected $primaryKey = 'companyhrruleid';
    public $timestamps = false;

    protected $fillable = [
        'companyid',
        'hrbaseruleid',
        'grouprule',
        'rulename',
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
        'hrbaseruleid' => 'integer',
        'selected' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    public function hrbaserule()
    {
        return $this->belongsTo(HRBaseRule::class,'hrbaseruleid', 'hrbaseruleid')
            ->where('hrbaseruleid', '>', 0); //tidak ambil data relasi kalau hrbaseruleid = 0
    }
}