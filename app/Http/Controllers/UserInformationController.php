<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInformation;

class UserInformationController extends Controller
{
    public function getUserDetailsViaEmail($email)
    {
        // Search for the user by email
        $user = User::where('email', $email)->first();

        // If the user is not found, return a 404 response
        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get user information, assuming user_information is linked to users
        $userInformation = UserInformation::where('user_id', $user->id)->first();

        // If user information is not found, return default user details
        if ($userInformation === null) {
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

        // If user information is found, return the user information
        return response()->json([
            'first_name' => $userInformation->first_name ?? $user->first_name,
            'middle_name' => $userInformation->middle_name ?? $user->middle_name,
            'last_name' => $userInformation->last_name ?? $user->last_name,
            'suffix' => $userInformation->suffix ?? $user->suffix,
            'date_of_birth' => $userInformation->date_of_birth ?? $user->date_of_birth,
            'gender' => $userInformation->gender ?? $user->gender,
            'contact_number' => $userInformation->contact_number ?? $user->contact_number,
            'complete_address' => $userInformation->complete_address ?? $user->complete_address,
            'email' => $user->email,
            'role_number' => $user->role_number,
        ], 200);
    }
}
