<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'course',
        'year',
        'block',
        'student_number',
        'time_in',
        'time_out',
        'status',
    ];
}
