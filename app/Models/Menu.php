<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';
    protected $primaryKey = 'menuid';
    public $timestamps = false; // karena Anda gunakan createdon/updatedon manual

    protected $fillable = [
        'menuname',
        'companyid',
        'parentmenuid',
        'ordersequence',
        'menutype',
        'hrgroup',
        'grouprole',
        'active',
        'sysrowid',
        'createdby',
        'createdon',
        'updatedby',
        'updatedon',
    ];

    // Casts
    protected $casts = [
        'hrgroup' => 'boolean',
        'active' => 'boolean',
        'companyid' => 'integer',
        'parentmenuid' => 'integer',
        'ordersequence' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyid', 'companyid');
    }

    /**
     * Mutator: saat menyet grouprole, normalisasi ke '#1#2#3#'
     * menerima string seperti '1#2#3' atau '#1#2#3#' atau array [1,2,3]
     */
    public function setGrouproleAttribute($value)
    {
        if (is_array($value)) {
            $ids = array_map('intval', $value);
        } else {
            $v = trim((string)$value);
            if ($v === '') {
                $this->attributes['grouprole'] = null;
                return;
            }

            // jika input berupa '#1#2#3#' -> trim lalu split
            $trimmed = trim($v, '#');
            if ($trimmed === '') {
                $this->attributes['grouprole'] = null;
                return;
            }
            $parts = preg_split('/#+/', $trimmed);
            $ids = array_values(array_filter(array_map('intval', $parts), function ($x) {
                return $x > 0;
            }));
        }

        // hapus duplikat, urutkan, lalu gabung -> format '#1#2#3#'
        $ids = array_values(array_unique($ids));
        if (count($ids) === 0) {
            $this->attributes['grouprole'] = null;
            return;
        }
        sort($ids, SORT_NUMERIC);
        $this->attributes['grouprole'] = '#' . implode('#', $ids) . '#';
    }

    /**
     * Accessor: read grouprole as string apa adanya
     */
    public function getGrouproleAttribute($value)
    {
        return $value;
    }

    /**
     * Helper: parse grouprole menjadi array integer [1,2,3]
     */
    public function getGrouproleIds(): array
    {
        $v = $this->attributes['grouprole'] ?? null;
        if (!$v) return [];
        $trimmed = trim($v, '#');
        if ($trimmed === '') return [];
        $parts = preg_split('/#+/', $trimmed);
        $ids = array_values(array_filter(array_map('intval', $parts), function ($x) {
            return $x > 0;
        }));
        return $ids;
    }
}
