<?php
namespace App\Http\Controllers;

use App\Models\LabSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LabScheduleController extends Controller
{
    // Display a listing of the lab schedules.
    public function index()
    {
        return response()->json(LabSchedule::all(), 200);
    }

    // Store a newly created lab schedule.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_code' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'instructor_name' => 'required|string|max:255',
            'block_id' => 'required|exists:blocks,id',
            'year' => 'required|string|max:255',
            'day_of_the_week' => 'required|string|max:255',
            'class_start' => 'required|string|max:255',
            'class_end' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $labSchedule = LabSchedule::create($request->all());

        return response()->json($labSchedule, 201);
    }

    // Display the specified lab schedule.
    public function show($id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        return response()->json($labSchedule, 200);
    }

    // Update the specified lab schedule.
    public function update(Request $request, $id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subject_code' => 'string|max:255',
            'subject_name' => 'string|max:255',
            'instructor_name' => 'string|max:255',
            'block_id' => 'exists:blocks,id',
            'year' => 'string|max:255',
            'day_of_the_week' => 'string|max:255',
            'class_start' => 'string|max:255',
            'class_end' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $labSchedule->update($request->all());

        return response()->json($labSchedule, 200);
    }

    // Remove the specified lab schedule.
    public function destroy($id)
    {
        $labSchedule = LabSchedule::find($id);

        if (!$labSchedule) {
            return response()->json(['message' => 'Lab schedule not found'], 404);
        }

        $labSchedule->delete();

        return response()->json(['message' => 'Lab schedule deleted'], 200);
    }
}


