<?php

namespace App\Http\Controllers;

use App\Models\LabAttendance;
use App\Models\RecentLogs;
use App\Models\Nfc;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecentLogsController extends Controller
{
    /**
     * Display a listing of all recent logs for users with role_id of 3.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Eager load the related models to avoid N+1 query problem
            $recentLogs = RecentLogs::with([
                'block',
                'nfc',
                'userInformation.user',
                'role',
                'seat.computer', // Ensure 'seat' and 'computer' relationships are correctly defined
            ])
                ->where('role_id', 3) // Filter logs with role_id of 3
                ->get()
                ->map(function ($log) {
                    return [
                        'user_name' => $log->user_name ?? ($log->userInformation->user->name ?? 'Unknown'),
                        'block_name' => $log->block->block ?? 'Unknown',
                        'year' => $log->year ?? 'Unknown',
                        'time_in' => $log->time_in ?? 'N/A',
                        'time_out' => $log->time_out ?? 'N/A',
                        'UID' => $log->nfc->rfid_number ?? 'Unknown',
                        'user_number' => $log->user_number ?? 'Unknown',
                        'block_id' => $log->block_id ?? 'Unknown',
                        'id_card_id' => $log->id_card_id ?? 'Unknown',
                        'role_name' => $log->role->name ?? 'Unknown',
                        'seat_number' => $log->seat->seat_number ?? 'Unassigned', // Assuming seat_number is a column in Seat
                        'computer_number' => $log->seat->computer->computer_number ?? 'Unassigned', // Assuming seat has a computer
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
        // Validate the input data
        $validated = $request->validate([
            'rfid_number' => 'required|string',
            'time_in' => 'required|date_format:H:i',
            'year' => 'required|integer',
            'role_id' => 'required|integer',
            'user_name' => 'required|string',
        ]);

        try {
            // Find the NFC record by rfid_number
            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Find associated user information
            $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();

            if (!$userInformation) {
                return response()->json(['message' => 'User information not found for this NFC UID.'], 404);
            }

            // Retrieve the correct course and schedule based on time and user information
            $course = $userInformation->courses()
                ->whereHas('labSchedules', function ($query) use ($validated) {
                    $query->where('class_start', '<=', $validated['time_in'])
                        ->where('class_end', '>=', $validated['time_in']);
                })
                ->first();

            if ($course) {
                $courseName = $course->course_name; // Adjust based on actual column name
                $schedule = $course->labSchedules()
                    ->where('class_start', '<=', $validated['time_in'])
                    ->where('class_end', '>=', $validated['time_in'])
                    ->first();
            } else {
                $courseName = 'Unknown';
                $schedule = null;
            }

            if (!$schedule) {
                return response()->json(['message' => 'No valid schedule found for the provided time.'], 404);
            }

            // Create a new log entry
            $log = RecentLogs::create([
                'user_number' => $userInformation->user_number,
                'block_id' => $userInformation->block_id,
                'year' => $validated['year'],
                'time_in' => $validated['time_in'],
                'id_card_id' => $nfc->id,
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
            ]);

            // Save the data to StudentAttendance table
            StudentAttendance::create([
                'user_information_id' => $userInformation->id,
                'time_in' => $validated['time_in'],
                'time_out' => null, // Initially null, will be updated later
                'status' => 'In Progress', // Assuming initial status
            ]);

            return response()->json(['message' => 'Time-In recorded successfully.', 'log' => $log], 201);
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
            // Find the NFC record by rfid_number
            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Find the existing log entry and update time-out
            $log = RecentLogs::where('id_card_id', $nfc->id)
                ->whereNotNull('time_in') // Ensure the log has a time_in
                ->whereNull('time_out') // Ensure the log doesn't have a time_out already
                ->first();

            if (!$log) {
                return response()->json(['message' => 'No matching time-in record found.'], 404);
            }

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
                    'status' => 'Completed', // Assuming status update on time-out
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
        // Validate the input data
        $validated = $request->validate([
            'rfid_number' => 'required|string',
        ]);

        try {
            // Find the NFC record by rfid_number
            $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();
            $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Fetch recent logs associated with this NFC UID
            $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user.course.instructor'])
                ->where('id_card_id', $nfc->id)
                ->get()
                ->map(function ($log) {
                    // Safely access nested relationships using null coalescing or optional chaining
                    $user = $log->userInformation->user ?? null;
                    $course = $user ? $user->course : null;
                    $instructor = $course ? $course->instructor : null;

                    return [
                        'date' => $log->created_at->toDateString(),
                        'name' => $log->user_name ?? ($user ? $user->name : 'Unknown'),
                        'pc_number' => $log->nfc->pc_number ?? 'Unknown',
                        'student_number' => $log->user_number ?? 'Unknown',
                        'year' => $log->year ?? 'Unknown',
                        'block' => $log->block->block ?? 'Unknown',
                        'instructor' => $instructor ? $instructor->name : 'Unknown',
                        'time_in' => $log->time_in,
                        'time_out' => $log->time_out,
                    ];
                });

            // Save each log entry to the StudentAttendance table if time_out is not null
            foreach ($recentLogs as $log) {
                if (!is_null($log['time_out'])) {
                    // Add a null coalescing operator for 'course' and other potentially missing keys
                    StudentAttendance::create([
                        'user_information_id' => $userInformation->id,
                        'time_in' => $log['time_in'],
                        'time_out' => $log['time_out'],
                        'status' => 'Completed',
                    ]);
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
        // Validate the input data
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
            'time_in' => 'required|string',
            'role_id' => 'required|integer',
            'user_name' => 'required|string',
        ]);

        try {
            \Log::info('Validated Input:', $validated);

            // Find the user by nested JSON structure
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            // Create a new log entry
            $log = RecentLogs::create([
                'user_number' => $user->user_number,
                'block_id' => $user->block_id,
                'year' => null,
                'time_in' => $validated['time_in'],
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
                'fingerprint_id' => $validated['fingerprint_id'],
            ]);

            // Save the data to LabAttendance table
            LabAttendance::create([
                'user_id' => $user->id,
                'seat_id' => null,
                'lab_schedule_id' => null,
                'time_in' => $validated['time_in'],
                'time_out' => null, // Initially null, will be updated later
                'status' => 'In Progress', // Assuming initial status
                'logdate' => now()->format('Y-m-d'),
                'instructor' => $user->name,
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
        // Validate the input data
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
            'time_out' => 'required|string',
        ]);

        try {
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            // Find the existing log entry and update time-out
            $log = RecentLogs::where('id_card_id', $user->id_card_id)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
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
                    'status' => 'Completed', // Assuming status update on time-out
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
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $user = User::find($userInformation->id);

            $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user', 'role'])
                ->where('id_card_id', $user->id_card_id)
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
        // Validate the input data
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Find the user information by email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json(['message' => 'Student not found'], 404);
            }

            // Fetch the user information record
            $userInformation = $user->userInformation;

            if (!$userInformation) {
                return response()->json(['message' => 'User information not found'], 404);
            }

            // Get the count of logs for the student
            $logCount = RecentLogs::where('user_number', $userInformation->user_number)->count();

            return response()->json([
                'email' => $validated['email'],
                'log_count' => $logCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
