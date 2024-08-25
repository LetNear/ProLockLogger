<?php
namespace App\Http\Controllers;

use App\Models\RecentLogs;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentLogsController extends Controller
{
    /**
     * Display a listing of all recent logs for users with role_id of 2.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Eager load the user, block, and userInformation relationships
        $recentLogs = RecentLogs::with(['user', 'block', 'userInformation'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', 3); // Change role_id to 3 if needed
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

    public function recordTimeOut(Request $request)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            'rfid_number' => 'required|string',
            'time_in' => 'required|date_format:H:i',
    
        ]);

        // Find the NFC record by rfid_number
        $nfc = Nfc::where('rfid_number', $validated['rfid_number'])->first();

        if (!$nfc) {
            return response()->json(['message' => 'NFC UID not found.'], 404);
        }

        // Create a new log entry, saving user_name and block_name
        $log = RecentLogs::create([
            'user_name' => $nfc->user->name, // Saving the user's name
            'block_name' => $nfc->block->block, // Saving the block name
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
=======
            'id_card_id' => 'required|string',
            'time_out' => 'required|date_format:H:i'
        ]);

        // Find the log entry and update time-out
        $log = RecentLogs::where('id_card_id', $validated['id_card_id'])
>>>>>>> parent of 0a19a7a (api)
            ->whereNotNull('time_in')
            ->first();

        if (!$log) {
            return response()->json(['message' => 'No matching time-in record found.'], 404);
        }

        $log->update([
            'time_out' => $validated['time_out'],
            'updated_at' => Carbon::now()
        ]);

        return response()->json(['message' => 'Time-Out recorded successfully.'], 200);
    }

    public function recordTimeIn(Request $request)
    {
        $validated = $request->validate([
            'id_card_id' => 'required|string',
            'time_in' => 'required|date_format:H:i'
        ]);

        // Find or create a log entry for the time-in
        $log = RecentLogs::updateOrCreate(
            [
                'id_card_id' => $validated['id_card_id'],
                'time_in' => null // Ensure we are updating the existing record if time_in is already set
            ],
            [
                'time_in' => $validated['time_in'],
                'updated_at' => Carbon::now()
            ]
        );

        return response()->json(['message' => 'Time-In recorded successfully.'], 200);
    }
}
