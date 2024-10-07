<?php

namespace App\Exports;

use App\Models\UserInformation;
use App\Models\LabSchedule;
use App\Models\StudentAttendance;
use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentAttExporter implements FromCollection, WithHeadings
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user(); // Current authenticated user
    }

    /**
     * Fetch the data collection to be exported.
     */
    public function collection()
    {
        $data = [];
        $scheduleDates = [];
    
        // Fetch all courses handled by the instructor (if the current user is an instructor)
        if ($this->user->role_number === 2) {  // Assuming role_number 2 is for instructors
            $courses = Course::where('instructor_id', $this->user->id)->get();  // Use instructor_id to fetch courses
        } else {
            $courses = Course::all();  // If the user is an admin or other role, fetch all courses
        }
    
        foreach ($courses as $course) {
            // Fetch all students enrolled in this course via the pivot table
            $students = UserInformation::whereHas('courses', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })->with('user', 'block')->get();
    
            // Fetch future schedules for the course, both regular and makeup classes
            $futureSchedules = LabSchedule::where('course_id', $course->id)->get();
    
            // Collect the schedule days and specific dates
            foreach ($futureSchedules as $schedule) {
                if ($schedule->is_makeup_class && $schedule->specific_date) {
                    // Use the specific date for makeup classes
                    $scheduleDates[] = Carbon::parse($schedule->specific_date)->format('Y-m-d');
                } else {
                    // For regular classes, calculate the next date for the given day of the week
                    $scheduleDates[] = Carbon::parse($schedule->created_at)->format('Y-m-d');
                }
            }
    
            // Add each student and future schedule details
            foreach ($students as $student) {
                // Prepare the row for each student, adding the attendance status for each schedule
                $row = [
                    'Name' => $student->user->name ?? 'N/A',
                    'Year' => $student->year ?? 'N/A',
                    'Block' => $student->block->block ?? 'N/A',
                    'Student Number' => $student->user_number ?? 'N/A',
                    'Course' => $course->course_name ?? 'N/A',
                ];
    
                // Add attendance status for each schedule date
                foreach ($scheduleDates as $date) {
                    // Fetch the student's **latest** StudentAttendance record for this schedule date
                    $attendance = StudentAttendance::where('user_information_id', $student->id)
                        ->where('course_id', $course->id)
                        ->orderBy('created_at', 'desc') // Fetch the latest attendance log
                        ->first();
    
                    if ($attendance) {
                        // Determine status based on the latest StudentAttendance record
                        if ($attendance->status === 'Completed') {
                            $row[$date] = 'Present';
                        } elseif ($attendance->status === 'Absent') {
                            $row[$date] = 'Absent';
                        }
                    } else {
                        // If no attendance record is found, mark as 'N/A'
                        $row[$date] = 'N/A';
                    }
                }
    
                // Add the completed row for the student
                $data[] = $row;
            }
        }
    
        return collect($data);
    }
    
    

    /**
     * Define the headings for the exported file.
     */
    public function headings(): array
    {
        // Basic student details headings
        $headings = [
            'Name',
            'Year',
            'Block',
            'Student Number',
            'Course'
        ];

        // Fetch all lab schedules for courses that are ongoing
        $futureSchedules = LabSchedule::whereDate('created_at', '>', Carbon::now()->subDays(7))
            ->orWhere('is_makeup_class', true)
            ->get();

        // Add the dates of each schedule (only the date, no time) to the headings
        foreach ($futureSchedules as $schedule) {
            if ($schedule->is_makeup_class && $schedule->specific_date) {
                // Use the specific_date for makeup classes (with format 'Y-m-d')
                $headings[] = Carbon::parse($schedule->specific_date)->format('Y-m-d');
            } else {
                // Use the created_at date (only the date part) for regular classes
                $headings[] = Carbon::parse($schedule->created_at)->format('Y-m-d');
            }
        }

        return $headings;
    }

    /**
     * Helper function to calculate the next date for a given day of the week (for regular classes).
     * @param string $dayOfWeek
     * @return string
     */
    private function getNextDateForDay($dayOfWeek)
    {
        $dayMapping = [
            'Monday' => Carbon::MONDAY,
            'Tuesday' => Carbon::TUESDAY,
            'Wednesday' => Carbon::WEDNESDAY,
            'Thursday' => Carbon::THURSDAY,
            'Friday' => Carbon::FRIDAY,
            'Saturday' => Carbon::SATURDAY,
            'Sunday' => Carbon::SUNDAY,
        ];

        $today = Carbon::now();
        $nextDate = $today->next($dayMapping[$dayOfWeek]); // Get the next occurrence of the given day

        return $nextDate->format('Y-m-d'); // Return only the date
    }
}