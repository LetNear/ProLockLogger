<?php

namespace App\Http\Controllers;

use App\Models\LabSchedule;
use App\Models\Nfc;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserInformation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class UserInformationController extends Controller
{

    public function index()
    {
        // Retrieve all user information records with related user data and NFC data
        $userInformations = UserInformation::with('user', 'idCard')
            ->whereHas('user', function ($query) {
                $query->where('role_number', 3); // Ensure that the user has role_id of 3
            })
            ->get();
    
        // Map the data to include user_number, user_name, and rfid_number (from NFC)
        $data = $userInformations->map(function ($userInformation) {
            return [
                'user_number' => $userInformation->user_number,
                'user_name' => $userInformation->user ? $userInformation->user->name : null, // Adjust 'name' if necessary
                'id_card_id' => $userInformation->idCard ? $userInformation->idCard->rfid_number : null, // Map to rfid_number
            ];
        });
    
        // Return as JSON response
        return response()->json($data);
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
                'year' => $user->year,
                'block' => $user->block ? $user->block->block : null, // Get the block name here
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
            'year' => $userInformation->year ?? $user->year,
            'block' => $userInformation->block ? $userInformation->block->block : null, // Get the block name here
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


    public function updateIdCardId(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'user_number' => 'required|string|max:255',
            'id_card_id' => 'required|string' // Use id_card_id to find or create the NFC record
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the NFC record exists
        $nfc = Nfc::where('rfid_number', $request->input('id_card_id'))->first();

        if (!$nfc) {
            // Create a new NFC record if it doesn't exist
            $nfc = Nfc::create([
                'rfid_number' => $request->input('id_card_id')
            ]);
        }

        // Find the user information by user_number
        $userInformation = UserInformation::where('user_number', $request->input('user_number'))->first();
        if (!$userInformation) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update the id_card_id with the ID from the NFC record
        $userInformation->id_card_id = $nfc->id;
        $userInformation->save();

        return response()->json(['message' => 'ID card updated successfully'], 200);
    }

    public function getIdCardId(Request $request): JsonResponse
    {
        // Validate the id_card_id parameter
        $request->validate([
            'id_card_id' => 'required|string',
        ]);

        // Find the NFC record by id_card_id
        $nfc = Nfc::where('rfid_number', $request->input('id_card_id'))->first();

        if (!$nfc) {
            return response()->json(['error' => 'NFC record not found'], 404);
        }

        // Find the user information associated with the NFC record
        $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();

        if (!$userInformation) {
            return response()->json(['error' => 'User information not found'], 404);
        }

        // Get the user details
        $user = $userInformation->user;

        return response()->json([
            'user_number' => $userInformation->user_number,
            'user_name' => $user ? $user->name : 'Unknown',
        ]);
    }
    public function getUserInformationByIdCardId(Request $request): JsonResponse
    {
        // Validate the id_card_id parameter
        $request->validate([
            'id_card_id' => 'required|string',
        ]);

        // Find the NFC record by id_card_id (rfid_number)
        $nfc = Nfc::where('rfid_number', $request->input('id_card_id'))->first();

        // Check if the NFC record exists
        if (!$nfc) {
            return response()->json(['error' => 'NFC record not found'], 404);
        }

        // Find the user information associated with the NFC record
        $userInformation = UserInformation::where('id_card_id', $nfc->id)->first();

        // Check if the user information exists
        if (!$userInformation) {
            return response()->json(['error' => 'User information not found'], 404);
        }

        // Get the user details from the related user model
        $user = $userInformation->user;

        // Prepare the response data
        $response = [
            'user_number' => $userInformation->user_number,
            'user_name' => $user ? $user->name : 'Unknown',
            'year' => $userInformation->year,
            'block' => $userInformation->block->block ?? 'Unknown',
            'email' => $user ? $user->email : 'Unknown',
            'first_name' => $userInformation->first_name ?? $user->first_name,
            'middle_name' => $userInformation->middle_name ?? $user->middle_name,
            'last_name' => $userInformation->last_name ?? $user->last_name,
            'suffix' => $userInformation->suffix ?? $user->suffix,
            'date_of_birth' => $userInformation->date_of_birth ?? $user->date_of_birth,
            'gender' => $userInformation->gender ?? $user->gender,
            'contact_number' => $userInformation->contact_number ?? $user->contact_number,
            'complete_address' => $userInformation->complete_address ?? $user->complete_address,
        ];

        // Return the user information as a JSON response
        return response()->json($response, 200);
    }

    public function updateIdCardIdByUserNumber(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'user_number' => 'required|string|max:255',
            'id_card_id' => 'required|string', // Use id_card_id to find or create the NFC record
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the NFC record exists
        $nfc = Nfc::where('rfid_number', $request->input('id_card_id'))->first();

        if (!$nfc) {
            // Create a new NFC record if it doesn't exist
            $nfc = Nfc::create([
                'rfid_number' => $request->input('id_card_id')
            ]);
        }

        // Find the user information by user_number
        $userInformation = UserInformation::where('user_number', $request->input('user_number'))->first();
        if (!$userInformation) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update the id_card_id with the ID from the NFC record
        $userInformation->id_card_id = $nfc->id;
        $userInformation->save();

        return response()->json(['message' => 'ID card updated successfully'], 200);
    }

    public function getUserInformationByUserNumber($user_number)
    {
        // Find the user information by user_number
        $userInformation = UserInformation::where('user_number', $user_number)->first();

        if (!$userInformation) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['user_information' => $userInformation], 200);
    }

    public function getStudentCountByInstructorEmail($email)
    {
        // Find the instructor by email
        $instructor = User::where('email', $email)->first();

        if (!$instructor) {
            return response()->json(['error' => 'Instructor not found'], 404);
        }

        // Get all the lab schedules for the instructor
        $labSchedules = LabSchedule::where('instructor_id', $instructor->id)->get();

        // Initialize the student count
        $studentCount = 0;

        // Iterate through each lab schedule to count students
        foreach ($labSchedules as $schedule) {
            $count = UserInformation::where('year', $schedule->year)
                ->where('block_id', $schedule->block_id)
                ->count();

            $studentCount += $count;
        }

        return response()->json(['student_count' => $studentCount], 200);
    }
}


// Find the NFC record by rfid_number
// $nfc = Nfc::where('rfid_number', $request->input('rfid_number'))->first();
// if (!$nfc) {
//     return response()->json(['error' => 'NFC record not found'], 404);
// }

// // Find the user information by user_number
// $userInformation = UserInformation::where('user_number', $request->input('user_number'))->first();
// if (!$userInformation) {
//     return response()->json(['error' => 'User not found'], 404);
// }

// // Update the id_card_id with the ID from NFC record
// $userInformation->id_card_id = $nfc->id;
// $userInformation->save();
