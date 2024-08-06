<?php

namespace App\Http\Controllers;

use App\Models\Nfc;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class NfcTagController extends Controller
{
    public function store(Request $request)
    {
        // Validate and process incoming data
        $validated = $request->validate([
            'tag_uid' => 'required|string|unique:nfc_tags,rfid_number',
        ]);

        // Log the NFC data
        Log::info('NFC Tag Data', $validated);

        // Save the data to the database
        $nfcTag = Nfc::firstOrCreate([
            'rfid_number' => $validated['tag_uid'],
        ]);

        // Return a JSON response with the created data and status code 201
        return response()->json(['message' => 'NFC tag data received', 'data' => $nfcTag], 201);
    }

    public function index()
    {
        return response()->json(Nfc::all(), 200);
    }

    public function show($id)
    {
        $nfcTag = Nfc::findOrFail($id);
        return response()->json($nfcTag, 200);
    }

    public function update(Request $request, $id)
    {

        $nfcTag = Nfc::findOrFail($id);

        $validated = $request->validate([
            'rfid_number' => 'sometimes|required|string|unique:nfc_tags,rfid_number,' . $nfcTag->id,
        ]);

        $nfcTag->update($validated);

        return response()->json(['message' => 'NFC tag data updated', 'data' => $nfcTag], 200);
    }

    public function destroy($id)
    {
        $nfcTag = Nfc::findOrFail($id);
        $nfcTag->delete();

        return response()->json(['message' => 'NFC tag data deleted'], 204);
    }
}
