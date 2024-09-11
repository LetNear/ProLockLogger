<?php

namespace App\Filament\Widgets;

use App\Models\LabSchedule;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Carbon\Carbon;
use Filament\Actions\Action;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;

class CalendarWidget extends FullCalendarWidget
{
    // Ensure that the type matches the parent class and initialize with null
    public Model|string|int|null $record = null;

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start'])->startOfDay();
        $end = Carbon::parse($fetchInfo['end'])->endOfDay();
        $events = [];

        $labSchedules = LabSchedule::query()
            ->where(function ($query) use ($start, $end) {
                $query->where('is_makeup_class', true)
                    ->whereBetween('specific_date', [$start, $end])
                    ->orWhere('is_makeup_class', false);
            })
            ->get();

        foreach ($labSchedules as $event) {
            if ($event->is_makeup_class) {
                $events[] = [
                    'title' => 'Makeup: ' . $event->course_name,
                    'start' => Carbon::parse($event->specific_date . ' ' . $event->class_start)->toIso8601String(),
                    'end' => Carbon::parse($event->specific_date . ' ' . $event->class_end)->toIso8601String(),
                    'shouldOpenUrlInNewTab' => true,
                ];
            } else {
                $events = array_merge($events, $this->getWeeklyOccurrences($event, $start, $end));
            }
        }

        return $events;
    }

    private function getWeeklyOccurrences(LabSchedule $event, Carbon $start, Carbon $end): array
    {
        $occurrences = [];
        $dayOfWeek = Carbon::parse($event->day_of_the_week)->dayOfWeek;
        $current = $start->copy()->next($dayOfWeek);

        while ($current->lte($end)) {
            $occurrences[] = [
                'title' => $event->course_name . ' - ' . $event->course_code,
                'start' => $current->copy()->setTimeFromTimeString($event->class_start)->toIso8601String(),
                'end' => $current->copy()->setTimeFromTimeString($event->class_end)->toIso8601String(),
                'shouldOpenUrlInNewTab' => true,
            ];
            $current->addWeek();
        }

        return $occurrences;
    }

    protected function getOptions(): array
    {
        return [
            'initialView' => 'resourceTimelineWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'resourceTimelineDay,resourceTimelineWeek',
            ],
            'events' => $this->fetchEvents(),
            'editable' => false,
            'selectable' => false,
            'eventClick' => fn($event) => false, // Disable event clicks
            'dateClick' => fn($date) => false,  // Disable date clicks
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make('view')
            ->label('View')
            ->disabled(true) // Disable the action
            ->visible(false); // Hide the action completely
    }
}
