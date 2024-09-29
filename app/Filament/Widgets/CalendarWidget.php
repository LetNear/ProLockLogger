<?php
namespace App\Filament\Widgets;

use App\Models\LabSchedule;
use App\Models\YearAndSemester;
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
        $start = Carbon::parse($fetchInfo['start'])->startOfDay()->setTimezone('Asia/Manila');
        $end = Carbon::parse($fetchInfo['end'])->endOfDay()->setTimezone('Asia/Manila');
        $events = [];
    
        // Fetch the active year and semester
        $activeYearAndSemester = YearAndSemester::where('status', 'on-going')->first();
    
        // Debug logging to verify start and end times
        \Log::info('Fetching events from:', [$start->toDateTimeString(), $end->toDateTimeString()]);
        \Log::info('Active Year and Semester:', [$activeYearAndSemester]);

        // Fetch lab schedules associated with the ongoing year and semester
        if ($activeYearAndSemester) {
            $labSchedules = LabSchedule::query()
                ->where('year_and_semester_id', $activeYearAndSemester->id)
                ->where(function ($query) use ($start, $end) {
                    $query->where('is_makeup_class', true)
                        ->whereBetween('specific_date', [$start, $end])
                        ->orWhere('is_makeup_class', false);
                })
                ->get();
                \Log::info('Lab Schedules Fetched:', [$labSchedules->count()]);

                
    
            foreach ($labSchedules as $event) {
                $instructorName = $event->instructor ? $event->instructor->name : 'No Instructor';
    
                if ($event->is_makeup_class) {
                    // Ensure correct parsing of specific date, class start, and end times
                    $specificDate = Carbon::parse($event->specific_date)->setTimezone('Asia/Manila');
                    $startTime = Carbon::createFromFormat('H:i', $event->class_start, 'Asia/Manila');
                    $endTime = Carbon::createFromFormat('H:i', $event->class_end, 'Asia/Manila');
    
                    // Combine date and time correctly, log for verification
                    $startDateTime = $specificDate->copy()->setTime($startTime->hour, $startTime->minute)->toIso8601String();
                    $endDateTime = $specificDate->copy()->setTime($endTime->hour, $endTime->minute)->toIso8601String();
    
                    \Log::info('Make-up Class Start:', [$startDateTime]);
                    \Log::info('Make-up Class End:', [$endDateTime]);
    
                    $events[] = [
                       'title' => '(Make-up Class) ' . $event->course_code . ' - ' . $instructorName,
                       'start' => $startDateTime,
                       'end' => $endDateTime,
                    ];
                } else {
                    $events = array_merge($events, $this->getWeeklyOccurrences($event, $start, $end, $instructorName));
                }
            }
        }
    
        // Log the final events array for debugging
        \Log::info('Final Events:', $events);
    
        return $events;
    }
    

    private function getWeeklyOccurrences(LabSchedule $event, Carbon $start, Carbon $end, $instructorName): array
    {
        $occurrences = [];
        $dayOfWeek = Carbon::parse($event->day_of_the_week)->dayOfWeek;
        $current = $start->copy()->next($dayOfWeek);

        while ($current->lte($end)) {
            $startTime = Carbon::createFromFormat('H:i', $event->class_start, 'Asia/Manila');
            $endTime = Carbon::createFromFormat('H:i', $event->class_end, 'Asia/Manila');

            $occurrences[] = [
                'title' => $event->course_code . ' - ' . $instructorName,
                'start' => $current->copy()->setTime($startTime->hour, $startTime->minute)->toIso8601String(),
                'end' => $current->copy()->setTime($endTime->hour, $endTime->minute)->toIso8601String(),
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
            'slotMinTime' => '00:00:00',
            'slotMaxTime' => '24:00:00',
            'timeZone' => 'Asia/Manila', // Set FullCalendar to display in Asia/Manila time zone
            'height' => 'auto',
            'contentHeight' => '90vh',
            'slotLabelInterval' => '01:00',
            'slotLabelFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'omitZeroMinute' => false,
            ],
            'eventDisplay' => 'block',
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
