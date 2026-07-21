<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderPart extends Model
{
    protected $connection = 'manufacturing';
    protected $fillable   = ['wo_id','product_id','name','category','status'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }

    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'wo_part_id');
    }
}
