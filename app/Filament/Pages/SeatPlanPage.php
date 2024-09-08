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
    public $students; // To store the list of students eligible for seat assignment
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

        // Initially fetch students without seats assigned
        $this->loadEligibleStudents();

        // Fetch courses associated with the instructor's regular schedules
        if (auth()->check() && auth()->user()->role_number == 2) {
            $this->courses = LabSchedule::where('instructor_id', auth()->user()->id)
                ->where('is_makeup_class', false)
                ->with('course')
                ->get()
                ->mapWithKeys(function ($schedule) {
                    $courseName = $schedule->course->course_name ?? 'Unknown Course';
                    $displayText = "{$schedule->day_of_the_week} - {$courseName}, {$schedule->class_start} - {$schedule->class_end}";
                    return [$schedule->course_id => $displayText];
                });
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role_number === 2;
    }

    public function updatedSelectedCourse($value)
    {
        $this->loadSeatPlanDetails();
        $this->loadEligibleStudents(); // Load eligible students whenever the selected course changes
    }

    public function loadSeatPlanDetails()
    {
        if (!empty($this->selectedCourse)) {
            $this->computers = Computer::all();

            $this->seats = Seat::where('course_id', $this->selectedCourse)
                ->with('computer', 'student.user')
                ->get()
                ->keyBy('computer_id');
        } else {
            $this->computers = collect();
            $this->seats = collect();
        }
    }

    public function loadEligibleStudents()
    {
        if ($this->selectedCourse) {
            // Fetch students enrolled in the selected course and not assigned to any seat
            $this->students = UserInformation::whereHas('user', function ($query) {
                $query->where('role_number', 3);
            })
                ->whereHas('courses', function ($query) {
                    $query->where('course_id', $this->selectedCourse);
                })
                ->whereNull('seat_id')
                ->get();
        } else {
            $this->students = collect();
        }
    }

    public function selectSeat($seatId)
    {


        // Fetch the seat associated with the computer ID
        $this->selectedSeat = Computer::where('id', $seatId)->first();

        // Debugging: Check the selected seat
        if (!$this->selectedSeat) {
            dd('No seat found for computer ID: ' . $seatId);
        }

        // Check the selected seat details

    }

    public function assignStudentToSeat()
    {
        if ($this->selectedStudent && $this->selectedSeat) {
            DB::transaction(function () {
                // Fetch the student using UserInformation model
                $student = UserInformation::find($this->selectedStudent);

                // Ensure the student exists and is valid
                if (!$student) {
                    dd('Student not found with ID: ' . $this->selectedStudent);
                }

                // Fetch the seat and ensure it exists
                $seat = Seat::where('computer_id', $this->selectedSeat->id)
                    ->where('course_id', $this->selectedCourse)
                    ->first();

                // Check if a seat entry exists for the selected computer and course
                if (!$seat) {
                    $seat = new Seat();
                    $seat->computer_id = $this->selectedSeat->id;
                    $seat->course_id = $this->selectedCourse;
                }

                // Check if the student is already assigned to another seat in the same course
                if ($student->seat_id && $student->seat_id !== $seat->id) {
                    dd('Student is already assigned to another seat.');
                }

                // Assign the student to the selected seat
                $seat->student_id = $student->id;
                $seat->instructor_id = auth()->user()->id; // Assuming instructor is logged in
                $seat->instructor_name = auth()->user()->name; // Assuming the instructor's name
                $seat->course_name = $student->courses->first()->course_name ?? null; // Assuming student has a course

                // Save the seat assignment
                $seat->save();

                // Update the student's seat_id
                $student->seat_id = $seat->id;
                $student->save();
            });

            // Reset selected seat and student to clear the form
            $this->reset(['selectedSeat', 'selectedStudent']);
            $this->loadSeatPlanDetails(); // Refresh seat details
            $this->loadEligibleStudents(); // Refresh eligible students list
        } else {
            dd('Missing selected student or seat');
        }
    }


    public function removeStudentFromSeat($seatId)
    {
        DB::transaction(function () use ($seatId) {
            $seat = Seat::find($seatId);

            if ($seat && $seat->student) {
                $student = UserInformation::find($seat->student->id);
                if ($student) {
                    $student->seat_id = null;
                    $student->save();
                }
            }

            $seat->delete();
        });

        $this->loadSeatPlanDetails();
        $this->loadEligibleStudents(); // Refresh eligible students list after removal
    }
}
