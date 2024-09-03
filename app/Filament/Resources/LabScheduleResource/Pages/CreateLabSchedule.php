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
        try {
            // Validate the schedule
            $this->validateSchedule($data);
        } catch (ValidationException $exception) {
            // Log the exception or handle it accordingly
            \Log::error('Validation failed: ' . $exception->getMessage());
            throw $exception; // rethrow to ensure Filament catches it
        }

        // Fetch course details if present
        if ($course = Course::find($data['course_id'] ?? null)) {
            $data['course_code'] = $course->course_code;
            $data['course_name'] = $course->course_name;
        }

        return $data;
    }

    protected function validateSchedule(array $data): void
    {
        $classStart = $data['class_start'];
        $classEnd = $data['class_end'];

        if (strtotime($classEnd) <= strtotime($classStart)) {
            Notification::make()
                ->title('Invalid Time Range')
                ->danger()
                ->body('Class end time must be after class start time.')
                ->send();

            throw ValidationException::withMessages([
                'class_end' => ['Class end time must be after class start time.'],
            ]);
        }

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
                'class_start' => ['This schedule conflicts with another schedule for the instructor.'],
            ]);
        }
    }
}
