<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        ]);

        // Create a new user with the provided name and fingerprint_id
        $user = User::create([
            'name' => $request->name,
            'fingerprint_id' => $request->fingerprint_id,
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
        ]);

        // Find the user and update their details
        $user = User::findOrFail($id);

        // Update user attributes if present in the request
        $user->name = $request->input('name', $user->name);
        $user->fingerprint_id = $request->input('fingerprint_id', $user->fingerprint_id);
        $user->save();

        return response()->json($user, 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(null, 204);
    }

    public function getUserByFingerprint($fingerprint_id)
    {
        // Search for the user by fingerprint_id
        $user = User::where('fingerprint_id', $fingerprint_id)->first();
        
        // If the user is not found, return a 404 response
        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        // If the user is found, return the user data with a 200 response
        return response()->json([
            'name' => $user->name,
            'fingerprint_id' => $user->fingerprint_id
        ], 200);
    }

    public function getUsersByRole()
    {
        // Retrieve all users with role_id of 2
        $users = User::where('role_number', 2)
                     ->get(['name', 'email']);
        
        // Return the users' name and email with a 200 response
        return response()->json($users, 200);
    }

    public function updateFingerprintByEmail(Request $request)
{
    // Validate the incoming request data
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'fingerprint_id' => 'required|string|max:255',
    ]);

    // Find the user by email
    $user = User::where('email', $request->email)->firstOrFail();

    // Update the user's fingerprint_id
    $user->fingerprint_id = $request->fingerprint_id;
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
    ], 200);
}


    
}
