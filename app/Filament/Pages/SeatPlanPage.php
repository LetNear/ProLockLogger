<?php
namespace App\Filament\Pages;

use App\Models\Block;
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
        // Initialize properties
        $this->seats = collect();
        $this->students = collect();
        $this->selectedBlockYear = null;
        $this->selectedStudent = null;
        $this->selectedSeat = null;

        // Fetch students without seats assigned
        $this->students = UserInformation::whereHas('user', function ($query) {
            $query->where('role_number', 3);
        })->whereNull('seat_id')->get();

        // Fetch instructor's blocks and years
        if (auth()->check() && auth()->user()->role_number == 2) {
            $this->instructorBlocksAndYears = LabSchedule::where('instructor_id', auth()->user()->id)
                ->join('blocks', 'lab_schedules.block_id', '=', 'blocks.id')
                ->select('blocks.block as block_name', 'lab_schedules.year')
                ->distinct()
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->block_name . '-' . $item->year => $item->block_name . ' - ' . $item->year];
                });
        }
    }

    public function updatedSelectedBlockYear($value)
    {
        // Load seat plan details when the selectedBlockYear changes
        $this->loadSeatPlanDetails();
    }

    public function loadSeatPlanDetails()
    {
        if (!empty($this->selectedBlockYear)) {
            // Debug output to verify initial selectedBlockYear value
          
    
            // Ensure there's a dash separating block and year
            if (strpos($this->selectedBlockYear, '-') !== false) {
                // Split the string by the dash
                $parts = explode('-', $this->selectedBlockYear);
    
                // Ensure we have exactly two parts
                if (count($parts) == 2) {
                    $blockName = trim($parts[0]);
                    $year = trim($parts[1]);
                } else {
                    $blockName = $this->selectedBlockYear;
                    $year = null;
                }
            } else {
                $blockName = $this->selectedBlockYear;
                $year = null;
            }
    
            // Debug output to verify extracted blockName and year
           
    
            // Fetch the block ID based on the block name
            $block = Block::where('block', $blockName)->first();
            $blockId = $block ? $block->id : null;
    
            // Debug output to verify blockId
         
    
            if ($blockId) {
                // Fetch seats based on block ID and year
                $this->seats = Seat::where('block_id', $blockId)
                                   ->where(function($query) use ($year) {
                                       if ($year) {
                                           $query->where('year', $year);
                                       }
                                   })
                                   ->get();
            } else {
                $this->seats = collect(); // or handle as needed
            }
    
            // Debug output to verify seats
         
        } else {
            $this->seats = collect(); // or handle as needed
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
