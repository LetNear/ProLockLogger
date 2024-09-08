<?php

namespace App\Filament\Pages;

use App\Models\LabSchedule;
use App\Models\Seat;
use App\Models\Computer;
use App\Models\UserInformation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SeatPlanPage extends Page
{
    public $computers; // Store the list of computers
    public $seats; // Store seat details to reflect assigned seats per course
    public $students;
    public $selectedStudent;
    public $selectedSeat;
    public $selectedCourse; // Replaces selectedBlockYear
    public $courses = []; // To store the courses available for the instructor

    protected static string $view = 'filament.pages.seat-plan-page';

    public function mount()
    {
        $this->computers = collect();
        $this->seats = collect();
        $this->students = collect();
        $this->selectedCourse = null;
        $this->selectedStudent = null;
        $this->selectedSeat = null;

        // Fetch students without seats assigned
        $this->students = UserInformation::whereHas('user', function ($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();

        // Fetch courses associated with the instructor's regular schedules
        if (auth()->check() && auth()->user()->role_number == 2) {
            // Assuming there's a field `is_regular` in the `LabSchedule` table
            $this->courses = LabSchedule::where('instructor_id', auth()->user()->id)
                ->where('is_makeup_class', false) // Filter to include only regular class schedules
                ->with('course')
                ->get()
                ->mapWithKeys(function ($schedule) {
                    $courseName = $schedule->course->course_name ?? 'Unknown Course';
                    $displayText = "{$schedule->day_of_the_week} - {$courseName}, {$schedule->class_start} - {$schedule->class_end}";
                    return [$schedule->course_id => $displayText]; // Use course_id to match seats
                });
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role_number === 2;
    }

    public function updatedSelectedCourse($value)
    {
        // Load seat plan details when the selectedCourse changes
        $this->loadSeatPlanDetails();
    }

    public function loadSeatPlanDetails()
    {
        if (!empty($this->selectedCourse)) {
            // Fetch computers
            $this->computers = Computer::all();

            // Fetch seats based on the selected course
            $this->seats = Seat::where('course_id', $this->selectedCourse) // Ensure seats are filtered by course
                ->with('computer', 'student.user') // Load related computer and student data
                ->get()
                ->keyBy('computer_id'); // Key by computer_id for easy access
        } else {
            $this->computers = collect(); // Clear computers if no course is selected
            $this->seats = collect(); // Clear seats if no course is selected
        }
    }

    public function selectSeat($seatId)
    {
        $this->selectedSeat = Seat::find($seatId);
    }

    public function assignStudentToSeat()
    {
        if ($this->selectedStudent && $this->selectedSeat) {
            DB::transaction(function () {
                $student = UserInformation::find($this->selectedStudent);
                $student->seat_id = $this->selectedSeat->id;
                $student->save();

                $this->selectedSeat->student_id = $student->user_id;
                $this->selectedSeat->save();
            });

            $this->reset(['selectedSeat', 'selectedStudent']);
            $this->loadSeatPlanDetails();
        }
    }

    public function removeStudentFromSeat($seatId)
{
    DB::transaction(function () use ($seatId) {
        // Find the seat by ID
        $seat = Seat::find($seatId);

        if ($seat) {
            // If the seat has an associated student, unassign the seat from the student
            if ($seat->student) {
                $student = UserInformation::find($seat->student->id);
                if ($student) {
                    // Unassign the student from the seat
                    $student->seat_id = null;
                    $student->save();
                }
            }

            // Delete the entire seat record from the database
            $seat->delete();
        }
    });

    // Refresh the seat plan details after deletion
    $this->loadSeatPlanDetails();
}

}
