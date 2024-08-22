<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use Illuminate\Http\Request;

class FingerprintController extends Controller
{
    // Get only the names of all fingerprints
    public function index()
    {
        try {
            $fingerprints = Fingerprint::select('name')->get();  // Select only the 'name' field
            return response()->json($fingerprints, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve fingerprints.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store a new fingerprint with name and fingerprint data
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string', 
            'fingerprint' => 'required|file', // Assuming fingerprint data is sent as a file
        ]);

        try {
            $fingerprintData = file_get_contents($request->file('fingerprint')->getRealPath());

            $fingerprint = Fingerprint::create([
                'name' => $request->input('name'),
                'fingerprint' => $fingerprintData,
            ]);

            return response()->json(['message' => 'Fingerprint successfully stored!', 'data' => $fingerprint], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store fingerprint.', 'error' => $e->getMessage()], 500);
        }
    }
}
