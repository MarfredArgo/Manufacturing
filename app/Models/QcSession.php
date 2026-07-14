<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcSession extends Model
{
    protected $fillable = ['wo_id', 'build_type', 'tech'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }

    public function results()
    {
        return $this->hasMany(QcResult::class, 'session_id');
    }
}
