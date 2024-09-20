<?php
namespace App\Filament\Imports;

use App\Models\LabSchedule;
use App\Models\User;
use App\Models\Block;
use App\Models\Course;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import; // Correct import
use Illuminate\Support\Facades\Log;

class LabScheduleImporter extends Importer
{
    protected static ?string $model = LabSchedule::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('course_code')
                ->rules(['required', 'max:255']),
            ImportColumn::make('course_name')
                ->rules(['required', 'max:255']),
            ImportColumn::make('instructor_name')
                ->fillRecordUsing(function(){
                    return;
                })
                ->rules(['required', 'max:255']),
            ImportColumn::make('block_name')
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

        // Validate instructor name and fetch the instructor_id
        $instructor = User::where('name', $this->data['instructor_name']) // Correct column 'name'
            ->where('role_number', 2)
            ->first();

        if (!$instructor) {
            throw new RowImportFailedException('Instructor not found');
        }

        // Validate block name and get the block_id
        $block = Block::where('block', $this->data['block_name'])->first();

        if (!$block) {
            throw new RowImportFailedException('Block not found');
        }

        // Validate the course exists
        $course = Course::where('course_code', $this->data['course_code'])->first();
        if (!$course) {
            throw new RowImportFailedException('Course not found');
        }

        // Validate that class_end is after class_start
        if (strtotime($this->data['class_end']) <= strtotime($this->data['class_start'])) {
            throw new RowImportFailedException('Class end time must be after class start time.');
        }

        // Check for conflicting schedules
        $conflictingSchedule = LabSchedule::where('day_of_the_week', $this->data['day_of_the_week'])
            ->where('block_id', $block->id)
            ->where(function ($query) {
                $query->whereTime('class_start', '<', $this->data['class_end'])
                      ->whereTime('class_end', '>', $this->data['class_start']);
            })
            ->exists();

        if ($conflictingSchedule) {
            throw new RowImportFailedException('This schedule conflicts with another schedule in the laboratory on the same day.');
        }

        // Create the lab schedule
        return LabSchedule::create([
            'course_code'    => $this->data['course_code'],
            'course_name'    => $this->data['course_name'],
            'course_id'      => $course->id,
            'instructor_id'  => $instructor->id, // Use the instructor ID here
            'block_id'       => $block->id, // Use the block ID here
            'year'           => $this->data['year'],
            'day_of_the_week'=> $this->data['day_of_the_week'],
            'class_start'    => $this->data['class_start'],
            'class_end'      => $this->data['class_end'],
        ]);
    }

    // Use the correct Import class for the completed notification body
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your lab schedule import has completed with ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' successfully imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
