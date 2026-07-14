<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcResult extends Model
{
    protected $fillable = ['session_id', 'check_id', 'value', 'verdict', 'note'];

    public function session()
    {
        return $this->belongsTo(QcSession::class, 'session_id');
    }

    public function check()
    {
        return $this->belongsTo(QcTemplate::class, 'check_id');
    }
}
