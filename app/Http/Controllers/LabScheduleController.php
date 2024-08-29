<?php

namespace App\Http\Controllers;

use App\Models\LabSchedule;
use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Http\Request;
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
        // Find the instructor by fingerprint ID
        $instructor = User::where('fingerprint_id', $fingerprint_id)->first();

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

}



