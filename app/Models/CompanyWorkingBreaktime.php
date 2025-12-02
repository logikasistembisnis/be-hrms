<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyWorkingBreaktime extends Model
{
    use HasFactory;

    protected $table = 'companyworkingbreaktime';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'companyworkinghoursid',
        'starttime',
        'endtime',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'companyworkinghoursid' => 'integer',
    ];

    public function companyworkinghours()
    {
        return $this->belongsTo(CompanyWorkingHours::class, 'companyworkinghoursid', 'companyworkinghoursid');
    }
}