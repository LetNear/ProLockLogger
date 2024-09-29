<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LabSchedule;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\YearAndSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LabScheduleController extends Controller
{

    protected function getActiveYearAndSemester()
    {
        return YearAndSemester::where('status', 'on-going')->first(); // Fetch the record with status 'on-going'
    }

    // Display a listing of the lab schedules.
    public function index()
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $labSchedules = LabSchedule::where('year', $activeYearSemester->id)->get();

        return response()->json($labSchedules, 200);
    }


    // Store a newly created lab schedule.
    public function store(Request $request)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_code' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'instructor_name' => 'required|string|max:255',
            'block_id' => 'required|exists:blocks,id',
            'day_of_the_week' => 'required|string|max:255',
            'class_start' => 'required|string|max:255',
            'class_end' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Add the active year to the lab schedule
        $labSchedule = LabSchedule::create(array_merge($request->all(), ['year' => $activeYearSemester->id]));

        return response()->json($labSchedule, 201);
    }


    // Display the specified lab schedule.
    public function show($id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $labSchedule = LabSchedule::where('id', $id)
            ->where('year', $activeYearSemester->id)
            ->first();

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        return response()->json($labSchedule, 200);
    }


    // Update the specified lab schedule.
    public function update(Request $request, $id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $labSchedule = LabSchedule::where('id', $id)
            ->where('year', $activeYearSemester->id)
            ->first();

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_code' => 'string|max:255',
            'subject_name' => 'string|max:255',
            'instructor_name' => 'string|max:255',
            'block_id' => 'exists:blocks,id',
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
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $labSchedule = LabSchedule::where('id', $id)
            ->where('year', $activeYearSemester->id)
            ->first();

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $labSchedule->delete();

        return response()->json(['message' => 'Lab schedule deleted'], 200);
    }


    public function getFacultyScheduleByFingerprintId($fingerprint_id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $instructor = User::where('fingerprint_id', $fingerprint_id)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)
            ->where('year', $activeYearSemester->id)
            ->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor'], 404);
        }

        return response()->json($labSchedules, 200);
    }


    public function getFacultyScheduleByEmail($email)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        // Find the instructor by email and role_number (assuming role_number 2 represents an instructor)
        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();
    
        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }
    
        // Get current date and time to filter out past makeup classes
        $currentDateTime = now();
    
        // Get lab schedules for the instructor in the active year and semester
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->where(function ($query) use ($currentDateTime) {
                // Include both regular and makeup classes, but exclude past makeup classes
                $query->where('is_makeup_class', false) // Regular classes
                    ->orWhere(function ($subQuery) use ($currentDateTime) {
                        $subQuery->where('is_makeup_class', true)
                            ->whereRaw('CONCAT(specific_date, " ", class_end) >= ?', [$currentDateTime]); // Only future or ongoing makeup classes
                    });
            })
            ->get();
    
        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor'], 404);
        }
    
        // Format the response with block and year information
        $formattedSchedules = $labSchedules->map(function($schedule) {
            return [
                'course_code' => $schedule->course_code,
                'course_name' => $schedule->course_name,
                'day_of_the_week' => $schedule->day_of_the_week,
                'class_start' => $schedule->class_start,
                'class_end' => $schedule->class_end,
                'specific_date' => $schedule->specific_date,
                'is_makeup_class' => $schedule->is_makeup_class,
                'block' => $schedule->block->block ?? 'N/A',  // Assuming block is a related model, with 'block' field
                'year' => $schedule->year ?? 'N/A'    // Assuming block has a 'year' field
            ];
        });
    
        // Include the role_number in the response
        return response()->json([
            'instructor' => $instructor->name,
            'email' => $instructor->email,
            'role_number' => $instructor->role_number,
            'schedules' => $formattedSchedules
        ], 200);
    }
    
    
    


    public function getInstructorScheduleCountByEmail($email)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        // Find the instructor by email and role_number (assuming role_number 2 represents an instructor)
        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();
    
        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }
    
        // Count the instructor's schedules, excluding makeup classes
        $scheduleCount = LabSchedule::where('instructor_id', $instructor->id)
            ->where('year_and_semester_id', $activeYearSemester->id) // Ensure correct year/semester
            ->where('is_makeup_class', false) // Exclude makeup classes
            ->count();
    
        return response()->json([
            'instructor' => $instructor->name,
            'email' => $email,
            'schedule_count' => $scheduleCount,
        ], 200);
    }
    


    public function getNextScheduleTimeByEmail($email)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $instructor = User::where('email', $email)
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            return response()->json(['message' => 'Instructor not found'], 404);
        }

        $nextSchedule = LabSchedule::where('instructor_id', $instructor->id)
            ->where('year', $activeYearSemester->id)
            ->where('class_start', '>', now())
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
                'day_of_the_week' => $nextSchedule->day_of_the_week,
            ],
        ], 200);
    }


   /**
     * Get the total count of schedules for a student based on their email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentScheduleCountByEmail(Request $request)
    {
        // Get the active year and semester
        $activeYearSemester = $this->getActiveYearAndSemester();

        // Return error if no active year and semester is found
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Validate the email provided in the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        // If validation fails, return the errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the email from the request
        $email = $request->query('email');

        // Find the student using their email (from the users table)
        $student = UserInformation::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        // If the student is not found, return a 404 error
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Get the count of schedules the student is enrolled in for the active year and semester
        $scheduleCount = LabSchedule::whereIn('id', function ($query) use ($student, $activeYearSemester) {
                // Use the pivot table to get the schedules the student is enrolled in
                $query->select('schedule_id')
                      ->from('course_user_information')
                      ->where('user_information_id', $student->id)
                      ->where('year_and_semester_id', $activeYearSemester->id);
            })
            ->count();

        // Return the student email and schedule count
        return response()->json([
            'student' => $email,
            'schedule_count' => $scheduleCount,
        ], 200);
    }

    public function getLabScheduleDataByFingerprintId($fingerprint_id)
    {
        // Retrieve the active year and semester
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        // Check if there is an ongoing year and semester
        if (!$activeYearSemester) {
            // No ongoing year and semester, return an empty response or a specific message
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        // Find the instructor by fingerprint ID, accounting for JSON structure
        $instructor = User::whereJsonContains('fingerprint_id', ['fingerprint_id' => $fingerprint_id])->first();
    
        if (!$instructor) {
            // If the instructor is not found, return a not found response
            return response()->json(['message' => 'Instructor not found'], 404);
        }
    
        // Get the lab schedules for the instructor that are associated with the active year and semester
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)
            ->where('year_and_semester_id', $activeYearSemester->id) // Ensure the schedules match the active semester
            ->with(['course', 'block']) // Eager load related data
            ->get();
    
        // If no lab schedules are found, return a specific message
        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this instructor in the active year and semester.'], 404);
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
                'is_makeup_class' => $schedule->is_makeup_class,
                'specific_date' => $schedule->is_makeup_class ? $schedule->specific_date : null, // Include specific date only for makeup classes
            ];
        });
    
        // Return the formatted schedules
        return response()->json($formattedSchedules, 200);
    }
    
    


    public function getStudentScheduleByEmail($email)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Find the student by email
        $student = UserInformation::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Fetch lab schedules based on course_name, class_start, and class_end within the active year
        $labSchedules = LabSchedule::where('block_id', $student->block_id)
            ->where('year', $activeYearSemester->id)
            ->whereNotNull('course_name')
            ->whereNotNull('class_start')
            ->whereNotNull('class_end')
            ->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this student'], 404);
        }

        return response()->json($labSchedules, 200);
    }


    public function showSchedule()
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Eager load 'course' relationship to ensure 'course_code' and 'course_name' are available
        $weekSchedule = LabSchedule::with('course')
            ->where('year', $activeYearSemester->id)
            ->get()
            ->groupBy('day_of_the_week')
            ->map(function ($schedules) {
                return $schedules->mapToGroups(function ($schedule) {
                    return [
                        $schedule->class_start => [
                            'course_code' => $schedule->course->course_code ?? 'N/A',
                            'course_name' => $schedule->course->course_name ?? 'N/A',
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
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        // Find the student using the RFID number
        $student = UserInformation::whereHas('idCard', function ($query) use ($rfid_number) {
            $query->where('rfid_number', $rfid_number);
        })->first();
    
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
    
        // Retrieve schedules for the student using the join table 'course_user_information'
        $schedules = DB::table('course_user_information')
            ->join('lab_schedules', 'course_user_information.schedule_id', '=', 'lab_schedules.id')
            ->join('courses', 'course_user_information.course_id', '=', 'courses.id')
            ->where('course_user_information.user_information_id', $student->id)
            ->where('lab_schedules.year_and_semester_id', $activeYearSemester->id)
            ->select(
                'courses.course_name',
                'courses.course_code',
                'lab_schedules.class_start',
                'lab_schedules.class_end',
                'lab_schedules.day_of_the_week',
                'lab_schedules.is_makeup_class',  // Add the is_makeup_class field
                'lab_schedules.specific_date'    // Add the specific_date field
            )
            ->get();
    
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'No schedules found for this student'], 404);
        }
    
        // Format the response to include makeup class info
        $formattedSchedules = $schedules->map(function ($schedule) {
            return [
                'course_name' => $schedule->course_name,
                'course_code' => $schedule->course_code,
                'class_start' => $schedule->class_start,
                'class_end' => $schedule->class_end,
                'day_of_the_week' => $schedule->day_of_the_week,
                'is_makeup_class' => $schedule->is_makeup_class ? 1 : 0, // Show if it's a makeup class
                'specific_date' => $schedule->is_makeup_class ? $schedule->specific_date : 'N/A', // Show specific date if it's a makeup class
            ];
        });
    
        return response()->json($formattedSchedules, 200);
    }
    


    public function getAllLabSchedules()
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $labSchedules = LabSchedule::where('year', $activeYearSemester->id)->get();

        if ($labSchedules->isEmpty()) {
            return response()->json(['message' => 'No lab schedules found'], 404);
        }

        return response()->json($labSchedules, 200);
    }


    public function enrollStudentToCourse(Request $request)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'course_id' => 'required|exists:courses,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $student = UserInformation::whereHas('user', function ($query) use ($request) {
            $query->where('email', $request->email);
        })->first();
    
        if (!$student) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $course = Course::find($request->course_id);
    
        // Check if the student is already enrolled in the course within the active year
        if ($student->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(['message' => 'User is already enrolled in this course'], 409);
        }
    
        // Enroll the student in the course
        $student->courses()->attach($course->id);
    
        return response()->json(['message' => 'User enrolled successfully'], 201);
    }
    


    public function getEnrolledCoursesByEmail($email)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();
    
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }
    
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|exists:users,email',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $student = UserInformation::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
    
        if (!$student) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Get the courses the student is enrolled in using the pivot table
        $enrolledCourses = $student->courses()
            ->where('year', $activeYearSemester->id)
            ->withPivot('schedule_id')
            ->with(['labSchedules' => function ($query) {
                $query->select('id', 'subject_code', 'subject_name', 'class_start', 'class_end', 'day_of_the_week');
            }])
            ->get(['courses.id', 'courses.course_name', 'courses.course_code']);
    
        if ($enrolledCourses->isEmpty()) {
            return response()->json(['message' => 'No enrolled courses found for this user'], 404);
        }
    
        return response()->json($enrolledCourses, 200);
    }
    

    public function getCourseDetailsByEmail(Request $request)
    {
        // Validate the email input to ensure it's valid and exists in the users table
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

        // Retrieve the active year and semester
        $activeYearSemester = $this->getActiveYearAndSemester();

        // If no active year and semester is found, return an error
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Get all courses taught by the instructor within the active year and semester
        $courses = Course::where('instructor_id', $instructor->id)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->with(['labSchedules' => function ($query) use ($activeYearSemester) {
                // Filter lab schedules by the active year and semester
                $query->where('year_and_semester_id', $activeYearSemester->id)
                      ->with('block'); // Eager load the block relationship
            }])
            ->get();

        // If no courses are found, return a 404 error
        if ($courses->isEmpty()) {
            return response()->json(['message' => 'No courses found for this instructor.'], 404);
        }

        // Map the courses to retrieve details and schedule information including year and block
        $courseDetails = $courses->map(function ($course) {
            $labSchedule = $course->labSchedules->first(); // Get the first lab schedule for this course

            return [
                'course_name' => $course->course_name,
                'course_code' => $course->course_code,
                'course_description' => $course->course_description,
                'schedule_password' => $labSchedule ? $labSchedule->password : 'N/A', // Get password from lab schedule
                'year' => $labSchedule ? $labSchedule->year : 'N/A', // Get year from lab schedule
                'block' => $labSchedule && $labSchedule->block ? $labSchedule->block->block : 'N/A', // Get block from lab schedule
            ];
        });

        // Return the response with the course details
        return response()->json([
            'instructor' => $instructor->name,
            'course_details' => $courseDetails,
        ]);
    }


    /**
     * Update course details (course code, course description, and schedule password)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCourseDetails(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'courseCode' => 'required|string|max:255',
            'courseDescription' => 'required|string|max:255',
            'schedulePassword' => 'required|string|max:255',
        ]);

        // Find the course by the course code
        $course = Course::where('course_code', $validatedData['courseCode'])->first();

        // If the course is not found, return an error response
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Update the course description
        $course->course_description = $validatedData['courseDescription'];
        $course->save(); // Save the updated course description

        // Update the lab schedule password
        // Assuming the first lab schedule is updated here. You can modify the logic as per your needs.
        $labSchedule = LabSchedule::where('course_id', $course->id)->first();
        if ($labSchedule) {
            $labSchedule->password = $validatedData['schedulePassword'];
            $labSchedule->save(); // Save the updated schedule password
        }

        return response()->json(['message' => 'Course and schedule updated successfully'], 200);
    }

 /**
     * Get student schedule details by email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudentScheduleDetailsByEmail(Request $request): JsonResponse
    {
        // Validate the email provided in the request
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email', // Ensure the email exists in the users table
        ]);

        try {
            // Get the active year and semester
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            // Find the user by email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json(['message' => 'Student not found'], 404);
            }

            // Get the associated user information record
            $userInformation = $user->userInformation;

            if (!$userInformation) {
                return response()->json(['message' => 'User information not found'], 404);
            }

            // Ensure the student is enrolled in the active year and semester
            if ($userInformation->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'Student is not enrolled in the active year and semester.'], 404);
            }

            // Get the schedules the student is enrolled in via the pivot table
            $scheduleDetails = LabSchedule::whereIn('id', function ($query) use ($userInformation, $activeYearSemester) {
                $query->select('schedule_id')
                      ->from('course_user_information')
                      ->where('user_information_id', $userInformation->id)
                      ->where('year_and_semester_id', $activeYearSemester->id);
            })->with('course', 'block', 'instructor')  // Eager load related models
              ->get();

            if ($scheduleDetails->isEmpty()) {
                return response()->json(['message' => 'No schedules found for this student.'], 404);
            }

            // Map the schedule details for the response
            $formattedScheduleDetails = $scheduleDetails->map(function ($schedule) {
                return [
                    'course_code' => $schedule->course_code,
                    'course_name' => $schedule->course_name,
                    'day_of_the_week' => $schedule->day_of_the_week,
                    'class_start' => $schedule->class_start,
                    'class_end' => $schedule->class_end,
                    'specific_date' => $schedule->is_makeup_class ? $schedule->specific_date : null,
                    'instructor_name' => $schedule->instructor->name ?? 'N/A',
                    'block_name' => $schedule->block->block_name ?? 'N/A',
                ];
            });

            // Return the formatted schedule details
            return response()->json([
                'student_email' => $validated['email'],
                'schedules' => $formattedScheduleDetails,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}
