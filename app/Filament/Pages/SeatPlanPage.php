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
    public $selectedBlockYear;
    public $instructorBlocksAndYears = [];

    protected static string $view = 'filament.pages.seat-plan-page';

    public function mount()
    {
        // Ensure that seats is always an empty collection by default
        $this->seats = collect();

        $this->students = UserInformation::whereHas('user', function ($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();
        if (auth()->check() && auth()->user()->role_number == 2) {
            $this->instructorBlocksAndYears = LabSchedule::where('instructor_id', auth()->user()->id)
                ->get()
                ->pluck('block', 'year');
        }
        

        $this->seats = Seat::where('instructor_id', auth()->user()->id)
            ->get()
            ->groupBy(['block_id', 'year'])
        ;




    }

    public function updatedSelectedBlockYear($value)
    {
        $this->loadSeatPlanDetails();
    }

    public function loadSeatPlanDetails()
    {
        if ($this->selectedBlockYear) {
            list($block, $year) = explode('-', $this->selectedBlockYear);

            // Fetch the seats with the related student and computer data
            $this->seats = Seat::where('block_id', $block)
                ->where('year', $year)
                ->where('instructor_id', auth()->user()->id)
                ->with(['computer_id', 'student_id'])
                ->get();
        } else {
            $this->seats = collect();
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
                $seat->delete();
            }
        });

        $this->loadSeatPlanDetails();
    }
}
