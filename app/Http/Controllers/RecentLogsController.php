<?php
namespace App\Http\Controllers;

use App\Models\RecentLogs;
use App\Models\Nfc;
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
        // Eager load the user, block, and userInformation relationships
        $recentLogs = RecentLogs::with(['user', 'block', 'userInformation'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', 3); // Ensure role_id is set to 3
            })
            
            ->get()
            ->map(function ($log) {
                return [
                    'user_name' => $log->user->name ?? 'Unknown',
                    'block_name' => $log->block->block ?? 'Unknown',
                    'year' => $log->year,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                    'UID' => $log->nfc->rfid_number ?? 'Unknown', // Add rfid_number here
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
        $validated = $request->validate([
            'rfid_number' => 'required|string',
            'time_in' => 'required|date_format:H:i',
            'year' => 'required|integer',
            
        ]);

        // Find the NFC record by rfid_number
        $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

        if (!$nfc) {
            return response()->json(['message' => 'NFC UID not found.'], 404);
        }

        // Create a new log entry
        $log = RecentLogs::create([
            'user_id' => $nfc->user_id, // Assuming NFC is associated with a user
            'block_id' => $nfc->block_id, // Assuming NFC is associated with a block
            'year' => $validated['year'], // Use the provided year
            'time_in' => $validated['time_in'],
            'id_card_id' => $nfc->id, // Assuming id_card_id refers to NFC ID
        ]);

        return response()->json(['message' => 'Time-In recorded successfully.', 'log' => $log], 201);
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

        // Find the NFC record by rfid_number
        $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

        if (!$nfc) {
            return response()->json(['message' => 'NFC UID not found.'], 404);
        }

        // Find the existing log entry and update time-out
        $log = RecentLogs::where('id_card_id', $nfc->id)
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



        return response()->json(['message' => 'Time-Out recorded successfully.', 'log' => $log], 200);
    }
}
 