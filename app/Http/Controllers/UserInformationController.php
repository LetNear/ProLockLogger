<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Support\Facades\Validator;

class UserInformationController extends Controller
{

    public function index()
    {
        // Retrieve all user numbers
        $userNumbers = UserInformation::pluck('user_number');

        // Return as JSON response
        return response()->json([
            'user_numbers' => $userNumbers
        ]);
    }
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


    public function updateUserDetails(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => '|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => '|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'date_of_birth' => '|date',
            'gender' => '|string|max:10',
            'contact_number' => '|string|max:15',
            'complete_address' => '|string|max:255',
            'email' => '|email' // To identify the user
        ]);
    
        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Find the user by email in the 'users' table
        $user = User::where('email', $request->input('email'))->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // Find the user information by user ID
        $userInformation = UserInformation::where('user_id', $user->id)->first();
        if (!$userInformation) {
            return response()->json(['error' => 'User information not found'], 404);
        }
    
        // Update the user details
        $userInformation->first_name = $request->input('first_name');
        $userInformation->middle_name = $request->input('middle_name');
        $userInformation->last_name = $request->input('last_name');
        $userInformation->suffix = $request->input('suffix');
        $userInformation->date_of_birth = $request->input('date_of_birth');
        $userInformation->gender = $request->input('gender');
        $userInformation->contact_number = $request->input('contact_number');
        $userInformation->complete_address = $request->input('complete_address');
        $userInformation->save();
    
        // Return success response
        return response()->json(['message' => 'User details updated successfully'], 200);
    }
    

}
