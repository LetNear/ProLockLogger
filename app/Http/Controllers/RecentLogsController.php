<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Nfc;
use App\Models\Seat;
use App\Models\User;
use App\Models\Course;
use App\Models\RecentLogs;
use Illuminate\Http\Request;
use App\Models\LabAttendance;
use App\Models\UserInformation;
use App\Models\YearAndSemester;
use App\Models\StudentAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class RecentLogsController extends Controller
{

    protected function getActiveYearAndSemester()
    {
        return YearAndSemester::where('status', 'on-going')->first(); // Fetches the first record with status 'on-going'
    }


    /**
     * Display a listing of all recent logs for users with role_id of 3.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            // Eager load the related models and filter by active year and semester
            $recentLogs = RecentLogs::with([
                'block',
                'nfc',
                'userInformation.user',
                'role',
                'seat', // Eager load seat relationship
            ])
                ->where('role_id', 3)
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->get()
                ->map(function ($log) {
                    return [
                        'user_name' => $log->userInformation->user->name ?? 'Unknown',
                        'block_name' => $log->block->block ?? 'Unknown',
                        'year' => $log->year ?? 'Unknown',
                        'time_in' => $log->time_in ?? 'N/A',
                        'time_out' => $log->time_out ?? null,
                        'UID' => $log->nfc->rfid_number ?? 'Unknown',
                        'user_number' => $log->user_number ?? 'Unknown',
                        'block_id' => $log->block_id ?? 'Unknown',
                        'id_card_id' => $log->id_card_id ?? 'Unknown',
                        'role_name' => $log->role->name ?? 'Unknown',
                        'seat_id' => $log->seat->computer_id ?? 'Unassigned', // Emit the seat_id directly
                        'date' => $log->created_at ? Carbon::parse($log->created_at)->format('m/d/Y') : 'Unknown', // Formatting date with Carbon
                        'assigned_instructor' => $log->assigned_instructor ?? 'N/A',
                    ];
                });



            return response()->json($recentLogs, 200);
        } catch (\Exception $e) {
            \Log::error('An error occurred while fetching recent logs.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Record time-in using the NFC UID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createRecordTimeInByUID(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_number' => 'required|string',
            'time_in' => 'required|date_format:H:i',
            'year' => 'required|integer',
            'role_id' => 'required|integer',
            'user_name' => 'required|string',
        ]);
    
        try {
            $activeYearSemester = $this->getActiveYearAndSemester();
    
            if (!$activeYearSemester) {
                info('No active year and semester found.');
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }
    
            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();
    
            if (!$nfc) {
                info('NFC UID not found.');
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }
    
            $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();
    
            if (!$userInformation) {
                info('User information not found for this NFC UID.');
                return response()->json(['message' => 'User information not found for this NFC UID.'], 404);
            }
    
            // Check if the user information is associated with the active year and semester
            if ($userInformation->year_and_semester_id !== $activeYearSemester->id) {
                info('User is not associated with the active year and semester.');
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }
    
            // Log the current day and time to help debug
            $currentDay = now()->dayName;
            $timeIn = $validated['time_in'];
            info("Searching for course on day: {$currentDay}, time: {$timeIn}");
            info("Server date and time: " . now()->toDateTimeString());
    
            // Retrieve the correct course and schedule
            $course = $userInformation->courses()
                ->whereHas('labSchedules', function (Builder $query) use ($timeIn, $currentDay) {
                    $query->where('class_start', '<=', $timeIn)
                        ->where('class_end', '>=', $timeIn)
                        ->where('day_of_the_week', $currentDay);
                })
                ->first();
    
            info('Course found', [$course]);
    
            if (!$course) {
                info('No course found for the current time.');
                return response()->json(['message' => 'No course found for the current time.'], 404);
            }
    
            // Retrieve the schedule
            $labSchedule = $course->labSchedules()
                ->where('day_of_the_week', $currentDay)
                ->where('class_start', '<=', $timeIn)
                ->where('class_end', '>=', $timeIn)
                ->first();
    
            if (!$labSchedule) {
                info('No lab schedule found for the current time.');
                return response()->json(['message' => 'No lab schedule found for the current time.'], 404);
            }
    
            // Retrieve assigned seat for the specific course and schedule
            $assignedSeat = Seat::where('student_id', $userInformation->id)
                ->where('course_id', $course->id) // Match the schedule
                ->first();
    
            // Retrieve the computer details from the assigned seat
            $computer = $assignedSeat ? $assignedSeat->computer : null;
            $computerNumber = $computer ? $computer->computer_number : 'Unassigned';
    
            // Create a new log entry with the seat_id and instructor name
            $log = RecentLogs::create([
                'user_number' => $userInformation->user_number,
                'block_id' => $userInformation->block_id,
                'year' => $validated['year'],
                'time_in' => $validated['time_in'],
                'id_card_id' => $nfc->id,
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
                'year_and_semester_id' => $activeYearSemester->id,
                'seat_id' => $assignedSeat->id ?? null, // Assign seat_id if found
                'assigned_instructor' => $labSchedule->instructor->name ?? 'N/A', // Get the instructor name from the schedule
            ]);
    
            // Save the data to StudentAttendance table with the seat_id
            StudentAttendance::create([
                'user_information_id' => $userInformation->id,
                'course_id' => $course->id ?? null,
                'time_in' => $validated['time_in'],
                'time_out' => null,
                'status' => 'In Progress',
                'year_and_semester_id' => $activeYearSemester->id,
                'seat_id' => $assignedSeat->id ?? null, // Assign seat_id if found
            ]);
    
            // Prepare response including the assigned seat
            $response = [
                'message' => 'Time-In recorded successfully.',
                'log' => $log,
                'assigned_seat' => $assignedSeat ? [
                    'seat_id' => $assignedSeat->id,
                    'seat_number' => $computerNumber, // Include the computer number
                ] : 'Unassigned',
                'assigned_instructor' => $labSchedule->instructor->name ?? 'N/A',
            ];
    
            return response()->json($response, 201);
        } catch (\Exception $e) {
            \Log::error('An error occurred while creating the log entry.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
    







    /**
     * Record time-out using the NFC UID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createRecordTimeOutByUID(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_number' => 'required|string',
            'time_out' => 'required|date_format:H:i',
        ]);

        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Find the log entry and ensure it's for the active year and semester
            $log = RecentLogs::where('id_card_id', $nfc->id)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->first();

            if (!$log) {
                return response()->json(['message' => 'No matching time-in record found.'], 404);
            }

            // Check if the time_out is 00:00:00 or 00:00
            $status = ($validated['time_out'] === '00:00' || $validated['time_out'] === '00:00:00')
                ? 'Absent'
                : 'Completed';

            $log->update([
                'time_out' => $validated['time_out'],
                'updated_at' => now(),
            ]);

            // Update the corresponding StudentAttendance record
            StudentAttendance::whereHas('userInformation', function ($query) use ($log) {
                $query->where('user_number', $log->user_number);
            })
                ->whereNull('time_out')
                ->update([
                    'time_out' => $validated['time_out'],
                    'status' => $status, // Set status based on time_out
                ]);

            return response()->json(['message' => 'Time-Out recorded successfully.', 'log' => $log], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }




    /**
     * Get recent logs by NFC UID (rfid_number).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentLogsByUID(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_number' => 'required|string',
        ]);

        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            // Find NFC by RFID number
            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Find User Information by NFC ID
            $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();

            if (!$userInformation) {
                return response()->json(['message' => 'User information not found for this NFC UID.'], 404);
            }

            // Check if the user information is associated with the active year and semester
            if ($userInformation->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }

            // Fetch recent logs associated with this NFC UID
            $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user'])
                ->where('id_card_id', $nfc->id)
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->get()
                ->map(function ($log) {
                    $user = $log->userInformation->user ?? null;
                    return [
                        'date' => $log->created_at ? $log->created_at->toDateString() : 'Unknown',
                        'name' => $log->user_name ?? ($user ? $user->name : 'Unknown'),
                        'time_in' => $log->time_in ?? 'Unknown',
                        'time_out' => $log->time_out ?? null,
                    ];
                });

            // Save each log entry to the StudentAttendance table if time_out is not null
            foreach ($recentLogs as $log) {
                if (!is_null($log['time_out'])) {
                    StudentAttendance::updateOrCreate(
                        [
                            'user_information_id' => $userInformation->id,
                            'time_in' => $log['time_in'],
                            'time_out' => $log['time_out'],
                        ],
                        [
                            'status' => 'Completed',
                        ]
                    );
                }
            }

            return response()->json($recentLogs, 200);
        } catch (\Exception $e) {
            \Log::error('An error occurred while fetching recent logs by UID.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }





    /**
     * Record time-in using the fingerprint ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createRecordTimeInByFingerprintId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
            'time_in' => 'required|string',
            'role_id' => 'required|integer',
            'user_name' => 'required|string',
        ]);

        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            \Log::info('Validated Input:', $validated);

            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            // Check if the user information is associated with the active year and semester
            if ($user->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }

            // Create a new log entry
            $log = RecentLogs::create([
                'user_number' => $user->user_number,
                'block_id' => $user->block_id,
                'year' => $activeYearSemester->id,
                'time_in' => $validated['time_in'],
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
                'fingerprint_id' => $validated['fingerprint_id'],
                'year_and_semester_id' => $activeYearSemester->id,
            ]);

            // Save the data to LabAttendance table
            LabAttendance::create([
                'user_id' => $user->id,
                'seat_id' => null,
                'lab_schedule_id' => null,
                'time_in' => $validated['time_in'],
                'time_out' => null,
                'status' => 'In Progress',
                'logdate' => now()->format('Y-m-d'),
                'instructor' => $user->name,
                'year_and_semester_id' => $activeYearSemester->id,
            ]);

            return response()->json(['message' => 'Time-In recorded successfully.', 'log' => $log], 201);
        } catch (\Exception $e) {
            \Log::error('An error occurred while creating the log entry.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }





    /**
     * Record time-out using the fingerprint ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createRecordTimeOutByFingerprintId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
            'time_out' => 'required|string',
        ]);

        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            // Check if the user information is associated with the active year and semester
            if ($user->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }

            // Find the existing log entry and update time-out
            $log = RecentLogs::where('id_card_id', $user->id_card_id)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->first();

            if (!$log) {
                return response()->json(['message' => 'No matching time-in record found.'], 404);
            }

            $log->update([
                'time_out' => $validated['time_out'],
                'updated_at' => now(),
            ]);

            // Update the corresponding LabAttendance record
            LabAttendance::where('user_id', $user->id)
                ->whereNull('time_out')
                ->update([
                    'time_out' => $validated['time_out'],
                    'status' => 'Completed',
                ]);

            return response()->json(['message' => 'Time-Out recorded successfully.', 'log' => $log], 200);
        } catch (\Exception $e) {
            \Log::error('An error occurred while updating the time-out.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }



    public function getRecentLogsByFingerprintId(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
        ]);

        try {
            $activeYearSemester = $this->getActiveYearAndSemester();

            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            // Check if the user information is associated with the active year and semester
            if ($user->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }

            $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user', 'role'])
                ->where('id_card_id', $user->id_card_id)
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->get()
                ->map(function ($log) {
                    return [
                        'user_name' => $log->user_name ?? $log->userInformation->user->name ?? 'Unknown',
                        'block_name' => $log->block->block ?? 'Unknown',
                        'year' => $log->year,
                        'time_in' => $log->time_in,
                        'time_out' => $log->time_out,
                        'UID' => $log->nfc->rfid_number ?? 'Unknown',
                        'user_number' => $log->user_number,
                        'block_id' => $log->block_id,
                        'id_card_id' => $log->id_card_id,
                        'role_name' => $log->role->name ?? 'Unknown',
                    ];
                });

            foreach ($recentLogs as $log) {
                if (!is_null($log['time_out'])) {
                    LabAttendance::create([
                        'user_id' => $user->id,
                        'seat_id' => null,
                        'lab_schedule_id' => null,
                        'time_in' => $log['time_in'],
                        'time_out' => $log['time_out'],
                        'status' => 'Completed',
                        'logdate' => now()->format('Y-m-d'),
                        'instructor' => $user->name,
                    ]);
                }
            }

            return response()->json($recentLogs, 200);
        } catch (\Exception $e) {
            \Log::error('An error occurred while fetching and saving recent logs.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }






   /**
     * Get the total count of logs for a student by email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalLogsCountByEmail(Request $request): JsonResponse
    {
        // Validate that the email is provided in the query string and exists in the users table
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email', // Ensure the email exists in the users table
        ]);

        try {
            // Get the active year and semester
            $activeYearSemester = $this->getActiveYearAndSemester();

            // Return an error if no active year and semester is found
            if (!$activeYearSemester) {
                return response()->json(['message' => 'No active year and semester found.'], 404);
            }

            // Find the user by email
            $user = User::where('email', $validated['email'])->first();

            // If the user is not found, return an error
            if (!$user) {
                return response()->json(['message' => 'Student not found'], 404);
            }

            // Get the associated user information record
            $userInformation = $user->userInformation;

            // If user information is not found, return an error
            if (!$userInformation) {
                return response()->json(['message' => 'User information not found'], 404);
            }

            // Ensure the user is associated with the active year and semester
            if ($userInformation->year_and_semester_id !== $activeYearSemester->id) {
                return response()->json(['message' => 'User is not associated with the active year and semester.'], 404);
            }

            // Get the count of logs for the student in the active year and semester
            $logCount = RecentLogs::where('user_number', $userInformation->user_number)
                ->where('year_and_semester_id', $activeYearSemester->id)
                ->count();

            // Return the email and the log count
            return response()->json([
                'email' => $validated['email'],
                'log_count' => $logCount,
            ], 200);
        } catch (\Exception $e) {
            // Return an error response with the exception message
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}
