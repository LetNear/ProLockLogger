<?php
namespace App\Filament\Resources\LabScheduleResource\Pages;

use App\Filament\Resources\LabScheduleResource;
use App\Models\Course;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\LabSchedule;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateLabSchedule extends CreateRecord
{
    protected static string $resource = LabScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate the schedule to ensure no conflicts and proper time range
        $this->validateSchedule($data);

        // Fetch the course details and assign course_code and course_name before saving
        if ($course = Course::find($data['course_id'] ?? null)) {
            $data['course_code'] = $course->course_code;
            $data['course_name'] = $course->course_name;
        }

        return $data;
    }

    protected function validateSchedule(array $data): void
    {
        // Assuming 'class_start' and 'class_end' are already in the correct format (h:i A)
        $classStart = $data['class_start'];
        $classEnd = $data['class_end'];
    
        // Check if class end time is before or the same as class start time
        if (strtotime($classEnd) <= strtotime($classStart)) {
            Notification::make()    
                ->title('Invalid Time Range')
                ->danger()
                ->body('Class end time must be after class start time.')
                ->send();
    
            throw ValidationException::withMessages([
                'class_end' => 'Class end time must be after class start time.',
            ]);
        }
    
        // Check for overlapping schedules
        $conflictingSchedule = LabSchedule::where('day_of_the_week', $data['day_of_the_week'])
            ->where('instructor_id', $data['instructor_id'])
            ->where(function ($query) use ($classStart, $classEnd) {
                $query->where(function ($subQuery) use ($classStart, $classEnd) {
                    $subQuery->whereTime('class_start', '<', $classEnd)
                             ->whereTime('class_end', '>', $classStart);
                });
            })
            ->exists();
    
        if ($conflictingSchedule) {
            Notification::make()
                ->title('Schedule Conflict')
                ->danger()
                ->body('This schedule conflicts with another schedule for the instructor.')
                ->send();
    
            throw ValidationException::withMessages([
                'class_start' => 'This schedule conflicts with another schedule for the instructor.',
            ]);
        }
    }
    
}
