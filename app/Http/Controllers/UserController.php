<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        // Use whereJsonContains with wildcard syntax to search across all users' fingerprint_ids
        $users = User::whereJsonContains('fingerprint_id->*.fingerprint_id', $fingerprint_id)->get();

        // If no users are found, return a 404 response
        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found with this fingerprint ID'], 404);
        }

        // Format the response to include relevant user information
        $usersData = $users->map(function ($user) {
            return [
                'name' => $user->name,
                'fingerprint_id' => $user->fingerprint_id,
                'role_number' => $user->role_number,
            ];
        });

        // Return the list of users with a 200 response
        return response()->json($usersData, 200);
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
}
