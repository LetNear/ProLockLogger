<?php

namespace App\Filament\Pages;

use App\Models\LabSchedule;
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
    public $selectedSchedule;
    public $instructorSubjects = [];

    protected static string $view = 'filament.pages.seat-plan-page';

    public function mount()
    {
        // Fetch all seats with related student info
        $this->seats = Seat::with(['computer', 'student'])->get();

        // Fetch only students with role_number = 3 (Students) and who are not assigned to any seat
        $this->students = UserInformation::whereHas('user', function ($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();

        // Fetch the instructor's subjects if logged in as instructor
        if (auth()->check() && auth()->user()->role_number == 2) {
            $this->instructorSubjects = LabSchedule::where('instructor_id', auth()->user()->id)
                ->get(['subject_code', 'subject_name', 'block_id', 'year']);
        }
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
                $this->selectedSeat->student_id = $student->user_id; // Ensure you are updating the correct field
                $this->selectedSeat->save();
            });

            // Reset the selected seat and refresh the data
            $this->reset(['selectedSeat', 'selectedStudent']);
            $this->mount();
        }
    }

    // Remove a student from a seat
    public function removeStudentFromSeat($seatId)
    {
        DB::transaction(function () use ($seatId) {
            // Find the seat
            $seat = Seat::find($seatId);

            if ($seat) {
                // Check if there is an assigned student
                if ($seat->student) {
                    // Remove the student's seat assignment
                    $student = UserInformation::find($seat->student->id);
                    if ($student) {
                        $student->seat_id = null;
                        $student->save();
                    }
                }

                // Delete the seat record
                $seat->delete();
            }
        });

        // Refresh the page data
        $this->mount();
    }

    public function loadSeatPlanDetails()
    {
        if ($this->selectedSchedule) {
            $scheduleDetails = json_decode($this->selectedSchedule, true);

            if (isset($scheduleDetails['block']) && isset($scheduleDetails['year'])) {
                $this->seats = Seat::where('block_id', $scheduleDetails['block'])
                    ->where('year', $scheduleDetails['year'])
                    ->with(['computer', 'student'])
                    ->get();
            }
        } else {
            $this->seats = collect(); // Clear seats if no schedule is selected
        }
    }

    public function updatedSelectedSchedule($value)
    {
        $this->loadSeatPlanDetails();
    }
}


