<?php

namespace App\Http\Controllers;

use App\Models\LabSchedule;
use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LabScheduleController extends Controller
{
    // Display a listing of the lab schedules.
    public function index()
    {
        return response()->json(LabSchedule::all(), 200);
    }

    // Store a newly created lab schedule.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_code' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'instructor_name' => 'required|string|max:255',
            'block_id' => 'required|exists:blocks,id',
            'year' => 'required|string|max:255',
            'day_of_the_week' => 'required|string|max:255',
            'class_start' => 'required|string|max:255',
            'class_end' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $labSchedule = LabSchedule::create($request->all());

        return response()->json($labSchedule, 201);
    }

    // Display the specified lab schedule.
    public function show($id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        return response()->json($labSchedule, 200);
    }

    // Update the specified lab schedule.
    public function update(Request $request, $id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_code' => 'string|max:255',
            'subject_name' => 'string|max:255',
            'instructor_name' => 'string|max:255',
            'block_id' => 'exists:blocks,id',
            'year' => 'string|max:255',
            'day_of_the_week' => 'string|max:255',
            'class_start' => 'string|max:255',
            'class_end' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $labSchedule->update($request->all());

        return response()->json($labSchedule, 200);
    }

    // Remove the specified lab schedule.
    public function destroy($id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $labSchedule->delete();

        return response()->json(['message' => 'Lab schedule deleted'], 200);
    }

    public function getFacultyScheduleByFingerprintId($fingerprint_id)
    {
        // Find the instructor by fingerprint ID and role_number 2 (Faculty)
        $instructor = User::where('fingerprint_id', $fingerprint_id)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        // Get the lab schedules for the instructor
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor'], 404);
        }

        return response()->json($labSchedules, 200);
    }

    public function getFacultyScheduleByEmail($email)
    {
        // Find the instructor by email and role_number 2 (Faculty)
        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        // Get the lab schedules for the instructor
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor'], 404);
        }

        return response()->json($labSchedules, 200);
    }

    public function getInstructorScheduleCountByEmail($email)
    {
        // Find the instructor by email and role_number 2 (Faculty)
        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        // Get the count of lab schedules for the instructor
        $scheduleCount = LabSchedule::where('instructor_id', $instructor->id)->count();

        return response()->json([
            'instructor' => $instructor->name,
            'email' => $email,
            'schedule_count' => $scheduleCount
        ], 200);
    }

    public function getNextScheduleTimeByEmail($email)
    {
        // Find the instructor by email and role_number 2 (Faculty)
        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        // Get the next lab schedule for the instructor
        $nextSchedule = LabSchedule::where('instructor_id', $instructor->id)
            ->where('class_start', '>', now()) // Ensure it's a future schedule
            ->orderBy('class_start', 'asc')
            ->first();

        if (!$nextSchedule) {
            return response()->json(['message' => 'No upcoming schedules found for this instructor'], 404);
        }

        return response()->json([
            'instructor' => $instructor->name,
            'email' => $email,
            'next_schedule' => [
                'subject_code' => $nextSchedule->subject_code,
                'subject_name' => $nextSchedule->subject_name,
                'class_start' => $nextSchedule->class_start,
                'class_end' => $nextSchedule->class_end,
                'day_of_the_week' => $nextSchedule->day_of_the_week
            ]
        ], 200);
    }

    /**
     * Get the total count of lab schedules for a student based on email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentScheduleCountByEmail(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve the email from the request
        $email = $request->query('email');

        // Find the student by email
        $student = UserInformation::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Count the lab schedules based on block_id and year
        $scheduleCount = LabSchedule::where('block_id', $student->block_id)
            ->where('year', $student->year)
            ->count();

        return response()->json([
            'student' => $email,
            'schedule_count' => $scheduleCount
        ], 200);
    }

    public function getLabScheduleDataByFingerprintId($fingerprint_id)
    {
        // Find the instructor by fingerprint ID, accounting for JSON structure
        $instructor = User::whereJsonContains('fingerprint_id', ['fingerprint_id' => $fingerprint_id])->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        // Get the lab schedules for the instructor, eager loading related data
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)
            ->with(['course', 'block'])
            ->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor'], 404);
        }

        // Format the response with detailed information
        $formattedSchedules = $labSchedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'course_code' => $schedule->course->course_code ?? 'N/A',
                'course_name' => $schedule->course->course_name ?? 'N/A',
                'block' => $schedule->block->block ?? 'N/A',
                'year' => $schedule->year ?? 'N/A',
                'day_of_the_week' => $schedule->day_of_the_week,
                'class_start' => $schedule->class_start,
                'class_end' => $schedule->class_end,
            ];
        });

        return response()->json($formattedSchedules, 200);
    }

    public function getStudentScheduleByEmail($email)
    {
        // Find the student by email
        $student = UserInformation::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Get the lab schedules based on block_id and year
        $labSchedules = LabSchedule::where('block_id', $student->block_id)
            ->where('year', $student->year)
            ->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this student'], 404);
        }

        return response()->json($labSchedules, 200);
    }

    public function showSchedule()
    {
        // Eager load 'course' relationship to ensure 'course_code' and 'course_name' are available
        $weekSchedule = LabSchedule::with('course') // Eager loading the 'course' relationship
            ->get()
            ->groupBy('day_of_the_week')
            ->map(function ($schedules) {
                return $schedules->mapToGroups(function ($schedule) {
                    return [
                        $schedule->class_start => [
                            'course_code' => $schedule->course->course_code ?? 'N/A', // Safely access course_code
                            'course_name' => $schedule->course->course_name ?? 'N/A', // Safely access course_name
                            'class_start' => $schedule->class_start,
                            'class_end' => $schedule->class_end,
                        ]
                    ];
                });
            });

        // Pass the data to the Blade view
        return view('schedules.index', compact('weekSchedule'));
    }

    public function getLabScheduleOfStudentByRFID($rfid_number)
    {
        // Step 1: Find the student using the RFID number
        $student = UserInformation::whereHas('idCard', function ($query) use ($rfid_number) {
            $query->where('rfid_number', $rfid_number);
        })->first();

        // Check if the student exists
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Step 2: Retrieve schedules for the student using the join table 'course_user_information'
        $schedules = DB::table('course_user_information')
            ->join('lab_schedules', 'course_user_information.schedule_id', '=', 'lab_schedules.id')
            ->join('courses', 'course_user_information.course_id', '=', 'courses.id')
            ->where('course_user_information.user_information_id', $student->id)
            ->select(
                'courses.course_name',
                'courses.course_code',
                'lab_schedules.class_start',
                'lab_schedules.class_end',
                'lab_schedules.day_of_the_week'
            )
            ->get();

        // Check if there are any schedules
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this student'], 404);
        }

        // Return all schedules as a JSON response
        return response()->json($schedules, 200);
    }
}
