<?php
namespace App\Filament\Pages;

use App\Models\LabSchedule;
use App\Models\Seat;
use App\Models\UserInformation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SeatPlanPage extends Page
{
    public $seats;
    public $students;
    public $selectedStudent;
    public $selectedSeat;
    public $selectedCourse; // Replaces selectedBlockYear
    public $courses = []; // To store the courses available for the instructor

    protected static string $view = 'filament.pages.seat-plan-page';

    public function mount()
    {
        

        // Initialize properties
        $this->seats = collect();
        $this->students = collect();
        $this->selectedCourse = null;
        $this->selectedStudent = null;
        $this->selectedSeat = null;

        // Fetch students without seats assigned
        $this->students = UserInformation::whereHas('user', function ($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();

        // Fetch courses associated with the instructor's schedules
        if (auth()->check() && auth()->user()->role_number == 2) {
            $this->courses = LabSchedule::where('instructor_id', auth()->user()->id)
                ->with('course')
                ->get()
                ->mapWithKeys(function ($schedule) {
                    $courseName = $schedule->course->course_name ?? 'Unknown Course';
                    $displayText = "{$courseName} - {$schedule->day_of_the_week}, {$schedule->class_start} - {$schedule->class_end}";
                    return [$schedule->id => $displayText]; // Map schedule ID to display text
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
            // Fetch seats based on the selected course (use course_id instead of schedule_id)
            $this->seats = Seat::where('course_id', $this->selectedCourse) // Change to course_id
                ->with('computer', 'student.user') // Load related computer and student data
                ->get();
        } else {
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
            $seat = Seat::find($seatId);

            if ($seat && $seat->student) {
                $student = UserInformation::find($seat->student->id);
                if ($student) {
                    $student->seat_id = null;
                    $student->save();
                }
                $seat->student_id = null;
                $seat->save();
            }
        });

        $this->loadSeatPlanDetails();
    }
}
