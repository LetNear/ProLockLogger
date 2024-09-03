<?php

namespace App\Http\Controllers;

use App\Models\Door;
use App\Models\DoorController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OpenAndCloseLogController extends Controller
{
    public function openDoor(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->firstOrFail();

        // Get the current date and time
        $now = Carbon::now('Asia/Manila');
        $logDate = $now->format('Y-m-d');
        $openTime = $now->format('H:i:s');

        // Create a new log entry for door opening
        $log = Door::create([
            'instructor_name' => $user->name,
            'instructor_email' => $user->email,
            'open_time' => $openTime,
            'status' => 'open',
            'log_date' => $logDate,
        ]);

        // Return a JSON response with the created log and status code 201
        return response()->json([
            'message' => 'Door opened successfully.',
            'log' => $log,
        ], 201);
    }

    public function closeDoor(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->firstOrFail();

        // Get the current date and time
        $now = Carbon::now('Asia/Manila');
        $logDate = $now->format('Y-m-d');
        $closeTime = $now->format('H:i:s');

        // Create a new log entry for door closing
        $log = Door::create([
            'instructor_name' => $user->name,
            'instructor_email' => $user->email,
            'close_time' => $closeTime,
            'status' => 'close',
            'log_date' => $logDate,
        ]);

        // Return a JSON response with the created log and status code 201
        return response()->json([
            'message' => 'Door closed successfully.',
            'log' => $log,
        ], 201);
    }
}
