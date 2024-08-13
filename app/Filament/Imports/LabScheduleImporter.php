<?php

namespace App\Filament\Imports;

use App\Models\LabSchedule;
use App\Models\User;
use App\Models\Block;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Notifications\LabScheduleImportNotification;

class LabScheduleImporter extends Importer
{
    protected static ?string $model = LabSchedule::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('subject_code')
                ->rules(['required', 'max:255']),
            ImportColumn::make('subject_name')
                ->rules(['required', 'max:255']),
            ImportColumn::make('instructor_name')
                ->rules(['required', 'max:255']), // Assuming import by name
            ImportColumn::make('block_name')
                ->rules(['required', 'max:255']), // Assuming import by block name
            ImportColumn::make('year')
                ->rules(['required', 'in:1,2,3,4']), // Restrict year to specific values
            ImportColumn::make('day_of_the_week')
                ->rules(['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday']),
            ImportColumn::make('class_start')
                ->rules(['required', 'date_format:H:i']), // Ensure valid time format
            ImportColumn::make('class_end')
                ->rules(['required', 'date_format:H:i']), // Ensure valid time format
        ];
    }

    public function resolveRecord(): ?LabSchedule
    {
        $instructor = User::where('name', $this->data['instructor_name'])
                          ->where('role_number', 2)
                          ->first();
                          
        $block = Block::where('block', $this->data['block_name'])->first();

        // Ensure the instructor and block are found
        if (!$instructor) {
            $this->addError('instructor_name', 'Instructor not found or does not have the correct role.');
        }

        if (!$block) {
            $this->addError('block_name', 'Block not found.');
        }

        if ($this->hasErrors()) {
            throw ValidationException::withMessages($this->getErrors());
        }

        return LabSchedule::updateOrCreate(
            ['subject_code' => $this->data['subject_code']],
            [
                'subject_name' => $this->data['subject_name'],
                'instructor_id' => $instructor->id,
                'block_id' => $block->id,
                'year' => $this->data['year'],
                'day_of_the_week' => $this->data['day_of_the_week'],
                'class_start' => $this->data['class_start'],
                'class_end' => $this->data['class_end'],
            ]
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your lab schedule import has completed with ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' successfully imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    protected function sendCompletionNotification(Import $import): void
    {
        $user = auth()->user(); // Or fetch the relevant user to notify
        Notification::send($user, new LabScheduleImportNotification($import));
    }
}
