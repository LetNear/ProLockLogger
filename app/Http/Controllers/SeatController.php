<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seat;
use App\Models\UserInformation;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    public function assignSeat(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
            'student_id' => 'required|exists:user_informations,id',
        ]);

        DB::transaction(function () use ($request) {
            $seat = Seat::find($request->seat_id);
            $student = UserInformation::find($request->student_id);

            $student->seat_id = $seat->id;
            $student->save();

            $seat->user_information_id = $student->id;
            $seat->save();
        });

        return response()->json(['success' => true]);
    }

    public function removeStudent(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
        ]);

        DB::transaction(function () use ($request) {
            $seat = Seat::find($request->seat_id);

            if ($seat && $seat->user_information_id) {
                $student = UserInformation::find($seat->user_information_id);
                $student->seat_id = null;
                $student->save();

                $seat->user_information_id = null;
                $seat->save();
            }
        });

        return response()->json(['success' => true]);
    }
}
