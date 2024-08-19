<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserInformationController extends Controller
{
    public function getUserDetails(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Search for the user by email
        $user = User::where('email', $request->email)->first();

        // If the user is not found, return a 404 response
        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // If the user is found, return the user data with a 200 response
        return response()->json([
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'suffix' => $user->suffix,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'contact_number' => $user->contact_number,
            'complete_address' => $user->complete_address,
            'email' => $user->email,
            'role_number' => $user->role_number,
        ], 200);
    }
}