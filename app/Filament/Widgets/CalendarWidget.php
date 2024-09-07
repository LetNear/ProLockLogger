<?php

namespace App\Filament\Widgets;

use App\Models\LabSchedule;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Carbon\Carbon;

class CalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        // Convert fetchInfo dates to Carbon instances
        $start = Carbon::parse($fetchInfo['start'])->startOfDay();
        $end = Carbon::parse($fetchInfo['end'])->endOfDay();

        // Create an array to hold the events
        $events = [];

        // Fetch lab schedules
        $labSchedules = LabSchedule::query()
            ->where(function ($query) use ($start, $end) {
                // Makeup classes
                $query->where('is_makeup_class', true)
                    ->whereBetween('specific_date', [$start, $end]);

                // Regular classes
                $query->orWhere('is_makeup_class', false);
            })
            ->get();

        foreach ($labSchedules as $event) {
            if ($event->is_makeup_class) {
                // For makeup classes, we add the event only on the specific date
                $events[] = [
                    'title' => 'Makeup: ' . $event->course_name,
                    'start' => Carbon::parse($event->specific_date . ' ' . $event->class_start)->toIso8601String(),
                    'end' => Carbon::parse($event->specific_date . ' ' . $event->class_end)->toIso8601String(),
                    'url' => route('filament.admin.resources.lab-schedules.edit', ['record' => $event->id]),
                    'shouldOpenUrlInNewTab' => true,
                ];
            } else {
                // For regular classes, we add the event for each occurrence of the day within the range
                $events = array_merge($events, $this->getWeeklyOccurrences($event, $start, $end));
            }
        }

        return $events;
    }

    // Generate all occurrences of regular classes on their specific day of the week
    private function getWeeklyOccurrences(LabSchedule $event, Carbon $start, Carbon $end): array
    {
        $occurrences = [];

        // Find the first occurrence of the day of the week within the date range
        $dayOfWeek = Carbon::parse($event->day_of_the_week)->dayOfWeek; // Get the integer value for the day
        $current = $start->copy()->next($dayOfWeek); // Find the next occurrence of that day in the range

        while ($current->lte($end)) {
            // Add the event for this day
            $occurrences[] = [
                'title' => $event->course_name . ' - ' . $event->course_code,
                'start' => $current->copy()->setTimeFromTimeString($event->class_start)->toIso8601String(),
                'end' => $current->copy()->setTimeFromTimeString($event->class_end)->toIso8601String(),
                'url' => route('filament.admin.resources.lab-schedules.edit', ['record' => $event->id]),
                'shouldOpenUrlInNewTab' => true,
            ];

            // Move to the next occurrence of the day
            $current->addWeek();
        }

        return $occurrences;
    }

    protected function getOptions(): array
    {
        return [
            'initialView' => 'resourceTimelineWeek', // Use the resource timeline view
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'resourceTimelineDay,resourceTimelineWeek',
            ],

            'events' => $this->fetchEvents(),
        ];
    }

    // ->plugin(
    //     FilamentFullCalendarPlugin::make()
    //         ->schedulerLicenseKey('CC-Attribution-NonCommercial-NoDerivatives')
    //         ->timezone('Asia/Manila') // Set the timezone to the Philippines
    //         ->locale('en')     // Set the locale (you can change this to 'fil' for Filipino)
    //         ->plugins(['interaction', 'resourceTimeline'])
    //         ->config([
    //             'headerToolbar' => [
    //                 'left' => 'prev,next today',
    //                 'center' => 'title',
    //                 'right' => 'resourceTimelineDay,resourceTimelineWeek',
    //             ],
    //             'initialView' => 'resourceTimelineWeek', // Set initial view to resource timeline
    //             'nowIndicator' => true, // Show current time indicator
    //             // 'height' => '10', // Set calendar height to auto
    //         ])
    // );
}
