<?php

namespace App\Filament\Resources\LabScheduleResource\Pages;

use App\Filament\Resources\LabScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\LabSchedule;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use App\Models\Course;

class EditLabSchedule extends EditRecord
{
    protected static string $resource = LabScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['instructor_id'] = Course::find($data['course_id'])?->instructor_id;
        // Validate only if relevant fields are changed
        if ($this->isScheduleChanged($data)) {
            $this->validateSchedule($data);
        }

        return $data;
    }

    protected function isScheduleChanged(array $data): bool
    {
        // Compare current record's schedule-related fields with the new data
        $current = $this->record;

        return $current->class_start !== $data['class_start'] ||
            $current->class_end !== $data['class_end'] ||
            $current->day_of_the_week !== $data['day_of_the_week'] ||
            $current->instructor_id !== $data['instructor_id'] ||
            // $current->specific_date !== $data['specific_date'] ||
            $current->block_id !== $data['block_id'] ||  // Check if block is changed
            $current->year !== $data['year'] ||          // Check if year is changed
            $current->course_id !== $data['course_id'];  // Check if course is changed
    }

    protected function validateSchedule(array $data): void
    {
        $classStart = $data['class_start'];
        $classEnd = $data['class_end'];
        $classType = $data['is_makeup_class'] ?? false; // Check if it's a makeup class
    
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
            // Perform regular class conflict check here (existing logic)...
        }
    
        // **Makeup class conflict check (specific_date)**
        elseif ($classType && isset($data['specific_date'])) {
            // Only perform this check if it's a makeup class and `specific_date` exists
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
    
    

    /**
     * Validate that the same block and year for a course cannot have different instructors.
     */
    protected function validateUniqueInstructorForBlockYear(array $data): void
    {
        $existing = LabSchedule::where('course_id', $data['course_id'])
            ->where('block_id', $data['block_id'])
            ->where('year', $data['year'])
            ->where('instructor_id', '!=', $data['instructor_id'])
            ->exists();

        if ($existing) {
            Notification::make()
                ->title('Instructor Conflict')
                ->danger()
                ->body('This block and year combination for the course already has a different instructor.')
                ->send();

            throw ValidationException::withMessages([
                'instructor_id' => ['This block and year combination for the course already has a different instructor.'],
            ]);
        }
    }
}
