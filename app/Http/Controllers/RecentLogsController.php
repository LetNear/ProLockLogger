<?php

namespace App\Http\Controllers;

use App\Models\RecentLogs;
use Illuminate\Http\JsonResponse;

class RecentLogsController extends Controller
{
    /**
     * Display a listing of all recent logs.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Eager load the user, role, and block relationships
        $recentLogs = RecentLogs::with(['user', 'role', 'block'])->get();

        return response()->json($recentLogs);
    }
}
