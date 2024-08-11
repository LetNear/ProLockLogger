<?php

namespace App\Filament\Resources\LabScheduleResource\Pages;

use App\Filament\Resources\LabScheduleResource;
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
        $this->validateSchedule($data);
        return $data;
    }

    protected function validateSchedule(array $data): void
    {
        // Check if class end time is before class start time
        if ($data['class_end'] <= $data['class_start']) {
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
            ->where('instructor_id', $data['instructor_id']) // Check same instructor
            ->where(function ($query) use ($data) {
                $query->where('class_start', '<', $data['class_end'])
                      ->where('class_end', '>', $data['class_start']);
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
