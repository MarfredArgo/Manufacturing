<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $connection = 'manufacturing';
    protected $fillable = ['name', 'role', 'notes'];
}
