<?php

namespace App\Http\Controllers;

use App\Models\RecentLogs;
use App\Models\Nfc;
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
        // Eager load the block, nfc, and userInformation relationships
        $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user'])
            ->where('role_id', 3) // Filter by role_id directly in the RecentLogs model
            ->get()
            ->map(function ($log) {
                return [
                    'user_name' => $log->user_name, // Use user_name from the table or fallback to the relationship
                    'block_name' => $log->block->block ?? 'Unknown',
                    'year' => $log->year,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                    'UID' => $log->nfc->rfid_number ?? 'Unknown',
                    'user_number' => $log->user_number,
                    'block_id' => $log->block_id,
                    'id_card_id' => $log->id_card_id,
                    'role_name' => $log->role->name ?? 'Unknown', // Added role name for clarity
                ];
            });

        return response()->json($recentLogs);
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

            // Create a new log entry
            $log = RecentLogs::create([
                'user_number' => $userInformation->user_number, // Changed to user_number
                'block_id' => $userInformation->block_id,
                'year' => $validated['year'],
                'time_in' => $validated['time_in'],
                'id_card_id' => $nfc->id,
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
            ]);

            return response()->json(['message' => 'Time-In recorded successfully.', 'log' => $log], 201);
        } catch (\Exception $e) {
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

            if (!$nfc) {
                return response()->json(['message' => 'NFC UID not found.'], 404);
            }

            // Fetch recent logs associated with this NFC UID
            $recentLogs = RecentLogs::with(['block', 'nfc', 'userInformation.user.course.instructor'])
                ->where('id_card_id', $nfc->id)
                ->get()
                ->map(function ($log) {
                    return [
                        'date' => $log->created_at->toDateString(), // Assuming 'created_at' reflects the relevant date
                        'name' => $log->user_name ?? $log->user->user->name ?? 'Unknown',
                        'pc_number' => $log->nfc->pc_number ?? 'Unknown', // Assuming 'pc_number' is a field in the NFC model
                        'student_number' => $log->user_number,
                        'year' => $log->year ?? 'Unknown',
                        'block' => $log->block->block ?? 'Unknown',
                        'instructor' => $log->userInformation->user->course->instructor->name ?? 'Unknown', // Fetching instructor's name from the course relation
                        'time_in' => $log->time_in,
                        'time_out' => $log->time_out,
                    ];
                });

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
            // Debug: Log the validated data
            \Log::info('Validated Input:', $validated);

            // Use raw SQL to find the user by nested JSON structure
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            // Debug: Log the user information query result
            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            // Convert the result to a model instance if necessary
            $userInformation = User::find($userInformation->id);

            // Create a new log entry
            $log = RecentLogs::create([
                'user_number' => $userInformation->user_number,
                'block_id' => $userInformation->block_id,
                'year' => null, // Set the year to null
                'time_in' => $validated['time_in'],
                'role_id' => $validated['role_id'],
                'user_name' => $validated['user_name'],
                'fingerprint_id' => $validated['fingerprint_id'],
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
            // Use raw SQL to find the user by nested JSON structure
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            // Convert the result to a model instance if necessary
            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            $userInformation = User::find($userInformation->id); // Convert to User model if needed

            // Find the existing log entry and update time-out
            $log = RecentLogs::where('id_card_id', $userInformation->id_card_id)
                ->whereNotNull('time_in')  // Ensure the log has a time_in
                ->whereNull('time_out')    // Ensure the log doesn't have a time_out already
                ->first();

            if (!$log) {
                return response()->json(['message' => 'No matching time-in record found.'], 404);
            }

            // Update the time-out
            $log->update([
                'time_out' => $validated['time_out'],
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Time-Out recorded successfully.', 'log' => $log], 200);
        } catch (\Exception $e) {
            \Log::error('An error occurred while updating the time-out.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getRecentLogsByFingerprintId(Request $request): JsonResponse
    {
        // Validate the input data
        $validated = $request->validate([
            'fingerprint_id' => 'required|string',
        ]);

        try {
            // Use raw SQL to find the user by nested JSON structure
            $fingerprintId = $validated['fingerprint_id'];
            $userInformation = DB::table('users')
                ->whereRaw("JSON_SEARCH(fingerprint_id, 'one', ?, NULL, '$[*].fingerprint_id') IS NOT NULL", [$fingerprintId])
                ->first();

            if (!$userInformation) {
                \Log::warning('Fingerprint ID not found in nested JSON query.', ['fingerprint_id' => $fingerprintId]);
                return response()->json(['message' => 'Fingerprint ID not found.'], 404);
            }

            // Convert the result to a model instance if necessary
            $user = User::find($userInformation->id);

            // Fetch recent logs associated with this user's id_card_id
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

            // Save each recent log entry to the LabAttendance table
            foreach ($recentLogs as $log) {
                LabAttendance::create([
                    'user_id' => $user->id,
                    'seat_id' => null, // Set this according to your application's logic
                    'lab_schedule_id' => null, // Set this according to your application's logic
                    'time_in' => $log['time_in'],
                    'time_out' => $log['time_out'],
                    'status' => 'Completed', // Or another status relevant to your logic
                    'logdate' => now()->format('Y-m-d'), // Assuming today's date, adjust as necessary
                    'instructor' => $user->name, // Assuming the user's name is the instructor, adjust as necessary
                ]);
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
