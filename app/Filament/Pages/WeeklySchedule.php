<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\LabSchedule;
use Illuminate\Support\Collection;

class WeeklySchedule extends Page
{
    protected static string $view = 'filament.pages.weekly-schedule';


    protected static ?string $title = 'Schedule';

    protected static ?string $label = 'Schedule';

    protected static ?string $navigationGroup = 'Laboratory Management';
    
    public $weekSchedule = [];

    public function mount(): void
    {
        $this->weekSchedule = LabSchedule::all()->groupBy('day_of_the_week')->map(function ($daySlots) {
            return $daySlots->sortBy('class_start')->groupBy('class_start');
        })->toArray();
    }
}
