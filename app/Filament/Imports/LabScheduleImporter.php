<?php

namespace App\Filament\Imports;

use App\Models\LabSchedule;
use App\Models\User;
use App\Models\Block;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

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
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->rules(['required', 'max:255']),
            ImportColumn::make('block_name')
                ->fillRecordUsing(function ($record, $state) {
                    return;
                })
                ->rules(['required', 'max:255']),
            ImportColumn::make('year')
                ->rules(['required', 'in:1,2,3,4']),
            ImportColumn::make('day_of_the_week')
                ->rules(['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday']),
            ImportColumn::make('class_start')
                ->rules(['required', 'date_format:H:i']),
            ImportColumn::make('class_end')
                ->rules(['required', 'date_format:H:i']),
        ];
    }

    public function resolveRecord(): ?LabSchedule
    {
        Log::info('Importing lab schedule data:', $this->data);

        // Validate instructor name
        $instructor = User::where('name', $this->data['instructor_name'])
            ->where('role_number', 2)
            ->first();

        // Validate block name
        $block = Block::where('block', $this->data['block_name'])->first();

        // Check for duplicate subject_code
        $existingSchedule = LabSchedule::where('subject_code', $this->data['subject_code'])->first();

        if (!$instructor || !$block || $existingSchedule) {
            return null; // Skip processing for this schedule
        }

        return LabSchedule::create([
            'subject_code' => $this->data['subject_code'],
            'subject_name' => $this->data['subject_name'],
            'instructor_id' => $instructor->id,
            'block_id' => $block->id,
            'year' => $this->data['year'],
            'day_of_the_week' => $this->data['day_of_the_week'],
            'class_start' => $this->data['class_start'],
            'class_end' => $this->data['class_end'],
        ]);
    }

    public function afterImport()
    {
        // Store errors in session for display
        if (!empty($this->invalidInstructors)) {
            Session::flash('invalidInstructors', $this->invalidInstructors);
        }

        if (!empty($this->invalidBlocks)) {
            Session::flash('invalidBlocks', $this->invalidBlocks);
        }

        if (!empty($this->duplicateSchedules)) {
            Session::flash('duplicateSchedules', $this->duplicateSchedules);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your lab schedule import has completed with ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' successfully imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
