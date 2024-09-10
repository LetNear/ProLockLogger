<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\YearAndSemester;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{

    // In UserController.php
    protected function getActiveYearAndSemester()
    {
        return YearAndSemester::where('status', 'on-going')->first(); // Fetches the first record with status 'on-going'
    }


    public function index()
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $users = User::where('year_and_semester_id', $activeYearSemester->id)->get();

        return response()->json($users, 200);
    }


    public function store(Request $request)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'fingerprint_id' => 'required|string|max:255|unique:users',
            'role_number' => 'required|integer',
        ]);

        // Create a new user with the active year_and_semester_id
        $user = User::create([
            'name' => $request->name,
            'fingerprint_id' => $request->fingerprint_id,
            'role_number' => $request->role_number,
            'year_and_semester_id' => $activeYearSemester->id,
        ]);

        return response()->json($user, 201);
    }


    public function show($id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $user = User::where('id', $id)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->firstOrFail();

        return response()->json($user, 200);
    }

    public function update(Request $request, $id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Find the user and ensure it belongs to the active year and semester
        $user = User::where('id', $id)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->firstOrFail();

        // Validate the incoming request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'fingerprint_id' => 'sometimes|required|string|max:255|unique:users,fingerprint_id,' . $id,
            'role_number' => 'sometimes|required|integer',
        ]);

        // Update user attributes if present in the request
        $user->name = $request->input('name', $user->name);
        $user->fingerprint_id = $request->input('fingerprint_id', $user->fingerprint_id);
        $user->role_number = $request->input('role_number', $user->role_number);
        $user->save();

        return response()->json($user, 200);
    }


    public function destroy($id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $user = User::where('id', $id)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->firstOrFail();
        $user->delete();

        return response()->json(null, 204);
    }

    public function getUsersByFingerprint($fingerprint_id)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Retrieve users within the active year and semester
        $users = User::where('year_and_semester_id', $activeYearSemester->id)->get();

        // Filtering logic for fingerprint
        $filteredUsers = $users->filter(function ($user) use ($fingerprint_id) {
            $fingerprints = $user->fingerprint_id;

            if (is_string($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }

            if (!is_array($fingerprints)) {
                $fingerprints = [];
            }

            foreach ($fingerprints as $fingerprint) {
                if (isset($fingerprint['fingerprint_id']) && $fingerprint['fingerprint_id'] === $fingerprint_id) {
                    return true;
                }
            }

            return false;
        });

        if ($filteredUsers->isNotEmpty()) {
            $user = $filteredUsers->first();
            $fingerprints = $user->fingerprint_id;

            if (is_string($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }

            if (!is_array($fingerprints)) {
                $fingerprints = [];
            }

            $matchingFingerprint = collect($fingerprints)->firstWhere('fingerprint_id', $fingerprint_id);

            if ($matchingFingerprint) {
                return response()->json([
                    'fingerprint_id' => $matchingFingerprint['fingerprint_id'],
                    'name' => $user->name,
                    'email' => $user->email,
                ], 200);
            }
        }

        return response()->json(['message' => 'Fingerprint ID is not registered and is available.'], 200);
    }




    public function getUsersByRole()
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Retrieve all users with role_number of 2 and active year and semester
        $users = User::where('role_number', 2)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->get(['name', 'email', 'role_number', 'fingerprint_id']);

        return response()->json($users, 200);
    }

    public function getUsersByRole1()
    {
        // Retrieve all users with role_number of 2
        $users = User::where('role_number', 1)
            ->get(['name', 'email', 'role_number', 'fingerprint_id']);

        // Return the users' name, email, and role_number with a 200 response
        return response()->json($users, 200);
    }


    public function updateFingerprintByEmail(Request $request)
    {
        $activeYearSemester = $this->getActiveYearAndSemester();

        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'fingerprint_id' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->firstOrFail();

        $allUsers = User::all();
        $allFingerprints = [];
        foreach ($allUsers as $singleUser) {
            $fingerprints = $singleUser->fingerprint_id;
            if (!is_array($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }
            $allFingerprints = array_merge($allFingerprints, array_column($fingerprints, 'fingerprint_id'));
        }

        if (in_array($request->fingerprint_id, $allFingerprints)) {
            return response()->json(['message' => 'Fingerprint already exists across all users.'], 400);
        }

        $existingFingerprints = $user->fingerprint_id;
        if (!is_array($existingFingerprints)) {
            $existingFingerprints = json_decode($existingFingerprints, true) ?? [];
        }

        if (count($existingFingerprints) < 2) {
            $existingFingerprints[] = ['fingerprint_id' => $request->fingerprint_id];
        } else {
            return response()->json(['message' => 'Cannot add more than 2 fingerprints.'], 400);
        }

        $user->fingerprint_id = json_encode($existingFingerprints);
        $user->save();

        return response()->json($user, 200);
    }




    public function getUserByEmail($email)
    {
        // Get the active YearAndSemester record
        $activeYearSemester = $this->getActiveYearAndSemester();

        // Check if there is an active year and semester
        if (!$activeYearSemester) {
            return response()->json(['message' => 'No active year and semester found.'], 404);
        }

        // Search for the user by email within the active year and semester
        $user = User::where('email', $email)
            ->where('year_and_semester_id', $activeYearSemester->id)
            ->first();

        // If the user is not found, return a 404 response
        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // If the user is found, return the user data with a 200 response
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'fingerprint_id' => $user->fingerprint_id,
            'role_number' => $user->role_number,
        ], 200);
    }


    public function getCurrentDateTime()
    {
        // Get the current date and time
        $now = Carbon::now('Asia/Manila');

        // Extract detailed components of the current date and time
        $dayOfWeek = $now->format('l');            // Day of the week (e.g., 'Monday')
        $date = $now->format('d');                // Day of the month (e.g., '01')
        $year = $now->format('Y');                // Year (e.g., '2024')
        $month = $now->format('F');               // Month (e.g., 'September')
        $currentTime = $now->format('H:i');     // Current time (e.g., '14:30:00')

        // Return the detailed date and time components in JSON response
        return response()->json([
            'day_of_week' => $dayOfWeek,
            'date' => $date,
            'year' => $year,
            'month' => $month,
            'current_time' => $currentTime,
        ], 200);
    }
}
