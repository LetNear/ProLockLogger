<?php
namespace App\Http\Controllers;

use App\Models\RecentLogs;
use Illuminate\Http\JsonResponse;

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
}
