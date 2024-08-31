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
        // Load the schedules with related course data
        $this->weekSchedule = LabSchedule::with(['course', 'instructor', 'block'])
            ->get()
            ->groupBy('day_of_the_week')
            ->map(function ($daySlots) {
                return $daySlots->sortBy('class_start')->groupBy('class_start')->map(function ($slots) {
                    return $slots->map(function ($slot) {
                        return [
                            'course_code' => $slot->course->course_code ?? 'N/A',
                            'course_name' => $slot->course->course_name ?? 'N/A',
                            'class_start' => $slot->class_start,
                            'class_end' => $slot->class_end,
                        ];
                    });
                });
            })->toArray();
    }
}
