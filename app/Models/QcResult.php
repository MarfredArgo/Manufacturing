<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcResult extends Model
{
    protected $connection = 'manufacturing';
    protected $fillable = ['session_id', 'check_id', 'wo_part_id', 'value', 'verdict', 'note'];

    public function session()
    {
        return $this->belongsTo(QcSession::class, 'session_id');
    }

    public function part()
    {
        return $this->belongsTo(WorkOrderPart::class, 'wo_part_id');
    }

    public function check()
    {
        return $this->belongsTo(QcTemplate::class, 'check_id');
    }
}
