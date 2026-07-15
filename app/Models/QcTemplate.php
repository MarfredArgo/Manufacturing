<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcTemplate extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'build_type', 'category', 'name', 'tool', 'target', 'operator', 'unit'];
}
