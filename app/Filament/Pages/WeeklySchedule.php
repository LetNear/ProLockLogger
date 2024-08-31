<?php 
namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\LabSchedule;

class WeeklySchedule extends Page
{
    protected static string $view = 'filament.pages.weekly-schedule';

    protected static ?string $title = 'Schedule';

    protected static ?string $label = 'Schedule';

    protected static ?string $navigationGroup = 'Laboratory Management';
    
    public $weekSchedule = [];

    public function mount(): void
    {
        // Efficiently load the necessary fields, including related models
        $this->weekSchedule = LabSchedule::with(['course', 'instructor', 'block'])  // Eager load related models
            ->get(['day_of_the_week', 'class_start', 'class_end', 'course_id', 'instructor_id', 'block_id'])  // Select only the necessary fields
            ->groupBy('day_of_the_week')
            ->map(function ($daySlots) {
                return $daySlots->sortBy('class_start')->groupBy('class_start');
            })->toArray();
    }
}
