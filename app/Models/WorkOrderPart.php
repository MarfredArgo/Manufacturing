<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderPart extends Model
{
    protected $fillable = ['wo_id', 'name', 'category', 'status'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }
}
