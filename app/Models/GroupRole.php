<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupRole extends Model
{
    use HasFactory;

    protected $table = 'grouprole';
    protected $primaryKey = 'grouproleid';
    public $timestamps = false;

    protected $fillable = [
        'grouproleid',
        'grouprolecode',
        'grouprolename',
        'active',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    protected $casts = [
        'createdon' => 'datetime',
        'updatedon' => 'datetime',
        'selected' => 'boolean'
    ];
}