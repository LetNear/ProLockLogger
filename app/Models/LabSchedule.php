<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class LabSchedule extends Model implements Auditable
{
    use HasFactory;
    use AuditingAuditable;

    protected $fillable = [
        'course_id', // Include this in the fillable array
        'course_code', // Include this in the fillable array
        'course_name', // Include this in the fillable array
        'block_id',
        'instructor_id',
        'year',
        'day_of_the_week',
        'class_start',
        'class_end',
        'password',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    // Accessor for course code, which will be used in your Blade
    public function getCourseCodeAttribute()
    {
        return $this->course ? $this->course->course_code : 'N/A';
    }

    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->course_name : 'N/A';
    }
}
