<?php

namespace App\Http\Controllers;

use App\Models\RecentLogs;
use App\Models\Nfc;
use App\Models\UserInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                    'user_name' => $log->user_name ?? ($log->userInformation->user->name ?? 'Unknown'), // Use user_name from the table or fallback to the relationship
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
}