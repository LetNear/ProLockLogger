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

        // Add instructor_id to the data
        $data['instructor_id'] = Course::find($data['course_id'])?->instructor_id;

        return $data;
    }
    protected function validateSchedule(array $data): void
    {
        $classStart = $data['class_start'];
        $classEnd = $data['class_end'];
        $classType = $data['is_makeup_class'] ?? false;
    
        // Fetch the current Year and Semester
        $yearAndSemesterId = Course::find($data['course_id'])->year_and_semester_id ?? null;
    
        if (!$yearAndSemesterId) {
            throw ValidationException::withMessages([
                'course_id' => ['Unable to determine the Year and Semester for this course.'],
            ]);
        }
    
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
    
        // **Regular class conflict check (day_of_the_week)**
        if (!$classType && isset($data['day_of_the_week'])) {
            // Check for conflicts within the same year and semester
            $conflictingSchedule = LabSchedule::where('day_of_the_week', $data['day_of_the_week'])
                ->where('year_and_semester_id', $yearAndSemesterId) // Ensure same year and semester
                ->where(function ($query) use ($classStart, $classEnd) {
                    $query->where(function ($subQuery) use ($classStart, $classEnd) {
                        $subQuery->whereTime('class_start', '<', $classEnd)
                            ->whereTime('class_end', '>', $classStart);
                    });
                })
                ->where('id', '!=', $this->record->id ?? null) // Exclude current record if editing
                ->exists();
    
            if ($conflictingSchedule) {
                Notification::make()
                    ->title('Schedule Conflict')
                    ->danger()
                    ->body('This schedule conflicts with another regular schedule in the same Year and Semester.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'class_start' => ['This schedule conflicts with another regular schedule in the same Year and Semester.'],
                ]);
            }
        }
    
        // **Makeup class conflict check (specific_date)**
        elseif ($classType && isset($data['specific_date'])) {
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
    
            // Check for conflicts with other makeup classes within the same year and semester
            $conflictingMakeupSchedule = LabSchedule::where('specific_date', $data['specific_date'])
                ->where('year_and_semester_id', $yearAndSemesterId) // Ensure same year and semester
                ->where(function ($query) use ($classStart, $classEnd) {
                    $query->where(function ($subQuery) use ($classStart, $classEnd) {
                        $subQuery->whereTime('class_start', '<', $classEnd)
                            ->whereTime('class_end', '>', $classStart);
                    });
                })
                ->where('id', '!=', $this->record->id ?? null) // Exclude current record if editing
                ->exists();
    
            if ($conflictingMakeupSchedule) {
                Notification::make()
                    ->title('Makeup Class Conflict')
                    ->danger()
                    ->body('This makeup class conflicts with another makeup class in the same Year and Semester.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'specific_date' => ['This makeup class conflicts with another makeup class in the same Year and Semester.'],
                ]);
            }
    
            // Check for conflicts with regular classes within the same year and semester
            $conflictingRegularSchedule = LabSchedule::where('day_of_the_week', date('l', $specificDate)) // Day of the week from specific date
                ->where('year_and_semester_id', $yearAndSemesterId) // Ensure same year and semester
                ->where(function ($query) use ($classStart, $classEnd) {
                    $query->where(function ($subQuery) use ($classStart, $classEnd) {
                        $subQuery->whereTime('class_start', '<', $classEnd)
                            ->whereTime('class_end', '>', $classStart);
                    });
                })
                ->where('is_makeup_class', false) // Ensure it's a regular class
                ->where('id', '!=', $this->record->id ?? null) // Exclude current record if editing
                ->exists();
    
            if ($conflictingRegularSchedule) {
                Notification::make()
                    ->title('Schedule Conflict')
                    ->danger()
                    ->body('This makeup class conflicts with a regular schedule in the same Year and Semester.')
                    ->send();
    
                throw ValidationException::withMessages([
                    'specific_date' => ['This makeup class conflicts with a regular schedule in the same Year and Semester.'],
                ]);
            }
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
