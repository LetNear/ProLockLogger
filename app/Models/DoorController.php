<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoorController extends Model
{

    protected $fillable = [
        'instructor_name',
        'instructor_email',
        'open_time',
        'close_time',
        'status',
        'log_date',
    ];
    
    use HasFactory;
}
