<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDirectorate extends Model
{
    use HasFactory;

    protected $table = 'companydirectorate';
    protected $primaryKey = 'companydirectorateid';
    public $timestamps = false;

    protected $fillable = [
        'companydirectorateid',
        'companyid',
        'directoratename',
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

    public function divisions() 
    {
        return $this->hasMany(CompanyDivision::class, 'companydirectorateid', 'companydirectorateid');
    }
}