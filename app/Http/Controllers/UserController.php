<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all(), 200);
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'fingerprint_id' => 'required|string|max:255|unique:users',
            'role_number' => 'required|integer',
        ]);

        // Create a new user with the provided name, fingerprint_id, and role_number
        $user = User::create([
            'name' => $request->name,
            'fingerprint_id' => $request->fingerprint_id,
            'role_number' => $request->role_number,
        ]);

        // Return a JSON response with the created user and status code 201
        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'fingerprint_id' => 'sometimes|required|string|max:255|unique:users,fingerprint_id,' . $id,
            'role_number' => 'sometimes|required|integer',
        ]);

        // Find the user and update their details
        $user = User::findOrFail($id);

        // Update user attributes if present in the request
        $user->name = $request->input('name', $user->name);
        $user->fingerprint_id = $request->input('fingerprint_id', $user->fingerprint_id);
        $user->role_number = $request->input('role_number', $user->role_number);
        $user->save();

        return response()->json($user, 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }

    public function getUsersByFingerprint($fingerprint_id)
    {
        // Retrieve all users
        $users = User::all();
    
        // Filter users to find those with the specific fingerprint_id
        $filteredUsers = $users->filter(function ($user) use ($fingerprint_id) {
            $fingerprints = $user->fingerprint_id;
    
            // Ensure fingerprints is an array, decode if it's a JSON string
            if (is_string($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }
    
            // Ensure $fingerprints is an array before looping
            if (!is_array($fingerprints)) {
                $fingerprints = [];
            }
    
            // Search through each fingerprint object in the array
            foreach ($fingerprints as $fingerprint) {
                if (isset($fingerprint['fingerprint_id']) && $fingerprint['fingerprint_id'] === $fingerprint_id) {
                    return true; // Found matching fingerprint_id
                }
            }
    
            return false; // No match found in this user
        });
    
        // If users are found with the fingerprint_id, return their details
        if ($filteredUsers->isNotEmpty()) {
            // Extract the first matching user details
            $user = $filteredUsers->first();
            $fingerprints = $user->fingerprint_id;
    
            // Decode fingerprints if it's a JSON string
            if (is_string($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }
    
            // Ensure $fingerprints is an array
            if (!is_array($fingerprints)) {
                $fingerprints = [];
            }
    
            // Find the specific fingerprint within the array
            $matchingFingerprint = collect($fingerprints)->firstWhere('fingerprint_id', $fingerprint_id);
    
            // If a matching fingerprint is found, return the required details
            if ($matchingFingerprint) {
                return response()->json([
                    'fingerprint_id' => $matchingFingerprint['fingerprint_id'],
                    'name' => $user->name,
                    'email' => $user->email,
                ], 200);
            }
        }
    
        // If no users are found with the fingerprint_id, return a success message
        return response()->json(['message' => 'Fingerprint ID is not registered and is available.'], 200);
    }
    
    


    public function getUsersByRole()
    {
        // Retrieve all users with role_number of 2
        $users = User::where('role_number', 2)
            ->get(['name', 'email', 'role_number', 'fingerprint_id']);

        // Return the users' name, email, and role_number with a 200 response
        return response()->json($users, 200);
    }

    public function updateFingerprintByEmail(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'fingerprint_id' => 'required|string|max:255',  // Validate as a string with max length
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->firstOrFail();

        // Retrieve all users' fingerprints from the database
        $allUsers = User::all();

        // Extract all fingerprint IDs from all users
        $allFingerprints = [];
        foreach ($allUsers as $singleUser) {
            $fingerprints = $singleUser->fingerprint_id;
            // Ensure fingerprints are an array
            if (!is_array($fingerprints)) {
                $fingerprints = json_decode($fingerprints, true) ?? [];
            }
            $allFingerprints = array_merge($allFingerprints, array_column($fingerprints, 'fingerprint_id'));
        }

        // Check if the new fingerprint_id already exists in any user's fingerprints
        if (in_array($request->fingerprint_id, $allFingerprints)) {
            return response()->json(['message' => 'Fingerprint already exists across all users.'], 400);
        }

        // Retrieve the existing fingerprint_ids for the current user
        $existingFingerprints = $user->fingerprint_id;

        // Ensure existingFingerprints is an array
        if (!is_array($existingFingerprints)) {
            $existingFingerprints = json_decode($existingFingerprints, true) ?? [];
        }

        // Add the new fingerprint_id to the array if the count is less than 2
        if (count($existingFingerprints) < 2) {
            $existingFingerprints[] = ['fingerprint_id' => $request->fingerprint_id];
        } else {
            return response()->json(['message' => 'Cannot add more than 2 fingerprints.'], 400);
        }

        // Update the user's fingerprint_id field with the new array
        $user->fingerprint_id = json_encode($existingFingerprints);
        $user->save();

        // Return a JSON response with the updated user and status code 200
        return response()->json($user, 200);
    }



    public function getUserByEmail($email)
    {
        // Search for the user by email
        $user = User::where('email', $email)->first();

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
        $now = Carbon::now();

        // Format the date and time as 'Y-m-d H:i:s' (e.g., '2024-09-01 14:30:00')
        $currentDateTime = $now->format('Y-m-d H:i:s');

        // Return the current date and time in JSON response
        return response()->json([
            'current_date_time' => $currentDateTime,
        ], 200);
    }
}
