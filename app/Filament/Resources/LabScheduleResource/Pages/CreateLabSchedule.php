<?php

namespace App\Filament\Resources\LabScheduleResource\Pages;

use App\Filament\Resources\LabScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\LabSchedule;
use App\Models\Course;
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

        // Set 'specific_date' to null if it's a regular class
        if (empty($data['is_makeup_class']) || !$data['is_makeup_class']) {
            $data['specific_date'] = null; // Set Makeup Class Date to null for Regular classes
        } else {
            $data['day_of_the_week'] = null; // Set Day of the Week to null for Makeup classes
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
        $classType = $data['is_makeup_class'] ?? false; // Assume 'false' means regular class

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

        // Check for schedule conflicts based on the class type
        if (!$classType && isset($data['day_of_the_week'])) {
            // Regular class: check for conflicts using day_of_the_week
            $conflictingSchedule = LabSchedule::where('day_of_the_week', $data['day_of_the_week'])
                ->where('instructor_id', $data['instructor_id'])
                ->where('id', '!=', $this->record->id ?? null)
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
        } elseif ($classType && isset($data['specific_date'])) {
            // Makeup class: check for conflicts using specific_date
            $specificDate = strtotime($data['specific_date']);
            if ($specificDate < strtotime(date('Y-m-d'))) {
                Notification::make()
                    ->title('Invalid Makeup Class Date')
                    ->danger()
                    ->body('Makeup class date must be set to a future date.')
                    ->send();

                throw ValidationException::withMessages([
                    'specific_date' => ['Makeup class date must be set to a future date.'],
                ]);
            }

            $conflictingSchedule = LabSchedule::where('specific_date', $data['specific_date'])
                ->where('instructor_id', $data['instructor_id'])
                ->where('id', '!=', $this->record->id ?? null)
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
                    ->body('This schedule conflicts with another makeup class for the instructor on the specified date.')
                    ->send();

                throw ValidationException::withMessages([
                    'class_start' => ['This schedule conflicts with another makeup class for the instructor on the specified date.'],
                ]);
            }
        }
    }
}
