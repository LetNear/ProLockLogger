<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_information_id',
        'course_id',
        'time_in',
        'time_out',
        'status',
    ];

    public function userInformation()
    {
        return $this->belongsTo(UserInformation::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function getDurationAttribute()
    {
        $timeIn = Carbon::parse($this->time_in);
        $timeOut = Carbon::parse($this->time_out);
        return $timeOut->diff($timeIn)->format('%H:%I:%S');
    }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }

    public function getPresentOrAbsentAttribute()
    {
        // Retrieve the first lab schedule related to the student's courses and the current instructor
        $labSchedule = $this->userInformation->courses->flatMap->labSchedules->firstWhere('instructor_id', auth()->id());

        if ($labSchedule) {
            // Scheduled start and end times with 1-minute allowance added to the end
            $scheduledStart = Carbon::parse($labSchedule->class_start);
            $scheduledEnd = Carbon::parse($labSchedule->class_end)->addMinute(); // Add 1-minute allowance

            // Attendance start and end times
            $attendanceStart = Carbon::parse($this->time_in);
            $attendanceEnd = Carbon::parse($this->time_out);

            // Debugging logs to verify time calculations
            \Log::info("Scheduled Start: {$scheduledStart}, Scheduled End: {$scheduledEnd}");
            \Log::info("Attendance Start: {$attendanceStart}, Attendance End: {$attendanceEnd}");

            // Corrected logic: Mark Present if within or equal to allowed time; otherwise, Absent
            if ($attendanceStart->greaterThanOrEqualTo($scheduledStart) && $attendanceEnd->lessThanOrEqualTo($scheduledEnd)) {
                return 'Absent'; // This should be marked as Absent if outside bounds
            } else {
                return 'Present'; // Mark as Present if within bounds
            }
        }

        // Default to Absent if no matching lab schedule is found
        return 'Absent';
    }

    public function getAssociatedCourseAttribute()
    {
        // Get the first course name associated with the attendance and the instructor
        $course = $this->userInformation->courses->flatMap->labSchedules->firstWhere('instructor_id', auth()->id());
        return $course ? $course->course_name : 'N/A'; // Return 'N/A' if no course found
    }


    public function getClassTypeAttribute()
    {
        // Determine if the class is a makeup class or a regular class
        $schedule = $this->userInformation->courses->flatMap->labSchedules->firstWhere('instructor_id', auth()->id());
        if ($schedule) {
            return $schedule->is_makeup_class ? 'Makeup Class' : 'Regular Class'; // Check is_makeup_class flag
        }
        return 'Unknown'; // Return 'Unknown' if no schedule found
    }
    public function getNextScheduleDateAttribute()
    {
        // Logic to fetch the next schedule date, if applicable
        $nextSchedule = $this->userInformation->courses->flatMap->labSchedules->firstWhere('instructor_id', auth()->id());
        return $nextSchedule ? Carbon::parse($nextSchedule->specific_date)->format('Y-m-d') : 'N/A';
    }
}
