<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $connection   = 'manufacturing';
    protected $primaryKey   = 'id';
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = ['id','name','specs','status','due','due_date','source','assigned','range'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function parts()
    {
        return $this->hasMany(WorkOrderPart::class, 'wo_id')->orderBy('id');
    }

    public function qcSessions()
    {
        return $this->hasMany(QcSession::class, 'wo_id');
    }

    public function reworkOrders()
    {
        return $this->hasMany(ReworkOrder::class, 'wo_id');
    }
}
