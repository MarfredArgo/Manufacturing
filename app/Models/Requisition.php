<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    protected $connection = 'manufacturing';
    protected $fillable   = [
        'req_id','part_name','quantity','department',
        'requested_by','priority','wo_id','notes','date_requested','status',
    ];

    protected $casts = [
        'date_requested' => 'date',
    ];
}
