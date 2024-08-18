<?php 
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Seat;
use App\Models\UserInformation;
use Illuminate\Support\Facades\DB;

class SeatPlanPage extends Page
{
    public $seats;
    public $students;
    public $selectedStudent;
    public $selectedSeat;

    protected static string $view = 'filament.pages.seat-plan-page';

    public function mount()
    {
        // Fetch all seats with related student info
        $this->seats = Seat::with('userInformation')->get();

        // Fetch only students with role_number = 3 (Students)
        $this->students = UserInformation::whereHas('user', function($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();
    }

    // Select a seat to assign a student
    public function selectSeat($seatId)
    {
        $this->selectedSeat = Seat::find($seatId);
    }

    // Assign a student to the selected seat
    public function assignStudentToSeat()
    {
        if ($this->selectedStudent && $this->selectedSeat) {
            DB::transaction(function () {
                $student = UserInformation::find($this->selectedStudent);
                $student->seat_id = $this->selectedSeat->id;
                $student->save();

                // Update the seat to reflect the assigned student
                $this->selectedSeat->user_information_id = $student->id;
                $this->selectedSeat->save();
            });

            // Reset the selected seat and refresh the data
            $this->selectedSeat = null;
            $this->mount();
        }
    }

    // Remove a student from a seat
    public function removeStudentFromSeat($seatId)
    {
        DB::transaction(function () use ($seatId) {
            $seat = Seat::find($seatId);

            if ($seat && $seat->user_information_id) {
                $student = UserInformation::find($seat->user_information_id);
                $student->seat_id = null;
                $student->save();

                // Clear the seat's student assignment
                $seat->user_information_id = null;
                $seat->save();
            }
        });

        // Refresh the page
        $this->mount();
    }
}
