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
            \Log::error('Validation failed: ' . $exception->getMessage());
            throw $exception;
        }

        // Set 'specific_date' to null if it's a regular class
        if (empty($data['is_makeup_class']) || !$data['is_makeup_class']) {
            $data['specific_date'] = null;
        } else {
            $data['day_of_the_week'] = null;
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
        $classType = $data['is_makeup_class'] ?? false;
    
        // Validate time range
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
    
        // Check for overlapping schedules for regular class (day_of_the_week)
        if (!$classType && isset($data['day_of_the_week'])) {
            // Query for any overlapping schedules on the same day in the lab
            $conflictingSchedule = LabSchedule::where('day_of_the_week', $data['day_of_the_week'])
                ->where(function ($query) use ($classStart, $classEnd) {
                    // Check if any schedule overlaps with the new one
                    $query->where(function ($subQuery) use ($classStart, $classEnd) {
                        $subQuery->whereTime('class_start', '<', $classEnd)
                                 ->whereTime('class_end', '>', $classStart);
                    });
                })
                ->where('id', '!=', $this->record->id ?? null) // Exclude the current record when editing
                ->exists();
    
            if ($conflictingSchedule) {
                Notification::make()
                    ->title('Schedule Conflict')
                    ->danger()
                    ->body('This schedule conflicts with another schedule in the laboratory on the same day.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'class_start' => ['This schedule conflicts with another schedule in the laboratory on the same day.'],
                ]);
            }
        } elseif ($classType && isset($data['specific_date'])) {
            // Handle conflicts for makeup class (specific_date)
            $specificDate = $data['specific_date'];
    
            if (strtotime($specificDate) < strtotime(date('Y-m-d'))) {
                Notification::make()
                    ->title('Invalid Makeup Class Date')
                    ->danger()
                    ->body('Makeup class date must be set to a future date.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'specific_date' => ['Makeup class date must be set to a future date.'],
                ]);
            }
    
            // Query for overlapping schedules on the same specific date in the lab
            $conflictingSchedule = LabSchedule::where('specific_date', $specificDate)
                ->where(function ($query) use ($classStart, $classEnd) {
                    $query->where(function ($subQuery) use ($classStart, $classEnd) {
                        $subQuery->whereTime('class_start', '<', $classEnd)
                                 ->whereTime('class_end', '>', $classStart);
                    });
                })
                ->where('id', '!=', $this->record->id ?? null) // Exclude the current record when editing
                ->exists();
    
            if ($conflictingSchedule) {
                Notification::make()
                    ->title('Schedule Conflict')
                    ->danger()
                    ->body('This makeup class conflicts with another schedule in the laboratory on the same date.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'class_start' => ['This makeup class conflicts with another schedule in the laboratory on the same date.'],
                ]);
            }
        }
    
        // Additional validation: Ensure no duplicate schedule for the same course, instructor, block, and year
        $duplicateSchedule = LabSchedule::where('course_id', $data['course_id'])
            ->where('instructor_id', $data['instructor_id'])
            ->where('block_id', $data['block_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $this->record->id ?? null) // Exclude the current record when editing
            ->exists();
    
        if ($duplicateSchedule) {
            Notification::make()
                ->title('Duplicate Schedule')
                ->danger()
                ->body('There is already a schedule for this course, instructor, block, and year.')
                ->send();
    
            throw ValidationException::withMessages([
                'course_id' => ['There is already a schedule for this course, instructor, block, and year.'],
            ]);
        }
    }
    
    
    

    
    
    
    // /**
    //  * Validate that the same block and year for a course cannot have different instructors.
    //  */
    // protected function validateUniqueInstructorForBlockYear(array $data): void
    // {
    //     $existing = LabSchedule::where('course_id', $data['course_id'])
    //         ->where('block_id', $data['block_id'])
    //         ->where('year', $data['year'])
    //         ->where('instructor_id', '!=', $data['instructor_id'])
    //         ->exists();

    //     if ($existing) {
    //         Notification::make()
    //             ->title('Instructor Conflict')
    //             ->danger()
    //             ->body('This block and year combination for the course already has a different instructor.')
    //             ->send();

    //         throw ValidationException::withMessages([
    //             'instructor_id' => ['This block and year combination for the course already has a different instructor.'],
    //         ]);
    //     }
    // }
}
