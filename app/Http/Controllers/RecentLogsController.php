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
        // Eager load the user, role, and block relationships
        $recentLogs = RecentLogs::with(['user', 'block'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', 3);
            })
            ->get()
            ->map(function ($log) {
                return [
                    'user_name' => $log->user->name ?? 'Unknown',
                    'block_name' => $log->block->block ?? 'Unknown',
                    'year' => $log->year,
                    'time_in' => $log->time_in,
                    'time_out' => $log->time_out,
                ];
            });

        return response()->json($recentLogs);
    }
}
