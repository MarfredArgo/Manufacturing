<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReworkRequiredPart extends Model
{
    protected $connection = 'manufacturing';
    protected $fillable = ['rework_id', 'name', 'status', 'eta'];

    public function reworkOrder()
    {
        return $this->belongsTo(ReworkOrder::class, 'rework_id');
    }
}
