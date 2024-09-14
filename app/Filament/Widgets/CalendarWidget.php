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
            $instructorName = $event->instructor ? $event->instructor->name : 'No Instructor';

            if ($event->is_makeup_class) {
                $events[] = [
                    'title' => $event->course_code . ' - ' . $instructorName . ' (Make-up Class)',
                    'start' => Carbon::parse($event->specific_date . ' ' . $event->class_start)->toIso8601String(),
                    'end' => Carbon::parse($event->specific_date . ' ' . $event->class_end)->toIso8601String(),
                ];
            } else {
                $events = array_merge($events, $this->getWeeklyOccurrences($event, $start, $end, $instructorName));
            }
        }

        return $events;
    }

    private function getWeeklyOccurrences(LabSchedule $event, Carbon $start, Carbon $end, $instructorName): array
    {
        $occurrences = [];
        $dayOfWeek = Carbon::parse($event->day_of_the_week)->dayOfWeek;
        $current = $start->copy()->next($dayOfWeek);

        while ($current->lte($end)) {
            $occurrences[] = [
                'title' => $event->course_code . ' - ' . $instructorName,
                'start' => $current->copy()->setTimeFromTimeString($event->class_start)->toIso8601String(),
                'end' => $current->copy()->setTimeFromTimeString($event->class_end)->toIso8601String(),
            ];
            $current->addWeek();
        }

        return $occurrences;
    }

    protected function getOptions(): array
    {
        return [
            'initialView' => 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'timeGridDay,timeGridWeek',
            ],
            'events' => $this->fetchEvents(),
            'editable' => false,
            'selectable' => false,
            'eventClick' => fn($event) => false,
            'dateClick' => fn($date) => false,
           
            'height' => 'auto', // Makes the calendar height responsive
            'contentHeight' => '90vh', // Adjusts the calendar to fill 90% of the viewport height
            'slotLabelInterval' => '01:00', // Label every hour
            'slotLabelFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'omitZeroMinute' => false,
            ],

            'eventDisplay' => 'block', // Ensures events are displayed as blocks
            'eventContent' => function($event) {
                return [
                    'html' => '<div style="white-space:normal;">' . $event['title'] . '</div>',
                ];
            },
        ];
    }
    

    protected function viewAction(): Action
    {
        return ViewAction::make('view')
            ->label('View')
            ->disabled(true)
            ->visible(false);
    }
}
