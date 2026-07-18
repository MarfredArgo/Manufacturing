<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReworkFailedCheck extends Model
{
    protected $connection = 'manufacturing';
    public $timestamps = false;

    protected $fillable = ['rework_id', 'check_id', 'check_name', 'verdict', 'result', 'target', 'reason'];

    public function reworkOrder()
    {
        return $this->belongsTo(ReworkOrder::class, 'rework_id');
    }
}
