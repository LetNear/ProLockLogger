<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    /**
     * Get student attendance logs by instructor's email using GET method.
     */
    public function getStudentAttendanceByInstructor(Request $request)
    {
        // Validate the query parameter to ensure 'email' is provided and is a valid email format
        $request->validate([
            'email' => 'required|email|exists:users,email', // Validate email exists in the users table
        ]);

        // Find the instructor by email
        $instructor = User::where('email', $request->query('email'))->where('role_number', 2)->first();

        // If the instructor does not exist or the role isn't 'instructor'
        if (!$instructor) {
            return response()->json([
                'message' => 'Instructor with the provided email was not found.'
            ], 404);
        }

        // Get all courses taught by the instructor
        $courseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

        // Fetch all attendance records related to the courses taught by the instructor
        $attendanceLogs = StudentAttendance::whereIn('course_id', $courseIds)
            ->with(['userInformation.user', 'userInformation.block', 'userInformation.courses']) // Load the pivoted courses relationship
            ->get();

        // Map the attendance logs to replace course_id and user_information_id with actual names, year, and block
        $transformedLogs = $attendanceLogs->map(function ($attendance) {
            // Get the first course for the user via the pivot table
            $course = $attendance->userInformation->courses->first(); // Use first course, since it’s a many-to-many relationship

            return [
                'id' => $attendance->id,
                'course_name' => $course ? $course->course_name : 'N/A', // Get course name from pivot
                'student_name' => $attendance->userInformation && $attendance->userInformation->user
                    ? $attendance->userInformation->user->name
                    : 'N/A',
                'year' => $attendance->userInformation ? $attendance->userInformation->year : 'N/A',
                'block' => $attendance->userInformation && $attendance->userInformation->block
                    ? $attendance->userInformation->block->block
                    : 'N/A',
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out,
                'status' => $attendance->status,
                'created_at' => $attendance->created_at,
                'updated_at' => $attendance->updated_at,
            ];
        });

        // Return the response with the transformed attendance logs
        return response()->json([
            'instructor' => $instructor->name,
            'attendance_logs' => $transformedLogs,
        ]);
    }
}
