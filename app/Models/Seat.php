<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class Seat extends Model implements Auditable
{

    protected $fillable = [
        'computer_id',
        'instructor_id',
        'instructor_name',
        'year',
        'course_name',
        'block_id',
        'student_id',
        'lab_attendance_id',
        'course_id',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class, 'seat_id');
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class, 'computer_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function userInformations()
    {
        return $this->belongsTo(UserInformation::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function student()
    {
        return $this->belongsTo(UserInformation::class, 'student_id');
    }
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function labSchedule()
    {
        return $this->belongsTo(LabSchedule::class);
    }

    public function schedule()
    {
        return $this->belongsTo(LabSchedule::class, 'course_id'); // assuming course_id is being used for schedule_id
    }



    public static function boot()
    {
        parent::boot();

        static::deleting(function ($seat) {
            // Handle related deletions or updates here
            if ($seat->student) {
                $seat->student->update(['seat_id' => null]);
            }

            // Add logic to handle the deletion of associated data if needed
            // For example, you might want to update or delete related records
        });
    }

    use HasFactory;
    use AuditingAuditable;
}
