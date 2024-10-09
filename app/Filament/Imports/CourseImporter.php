<?php

namespace App\Filament\Imports;

use App\Models\Course;
use App\Models\LabSchedule;
use App\Models\User;
use App\Models\Block;
use App\Models\YearAndSemester;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CourseImporter extends Importer
{
    protected static ?string $model = Course::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('course_name')
                ->rules(['required', 'max:255']),
            ImportColumn::make('course_code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('instructor_name')
                ->fillRecordUsing(function () {
                    return;
                })
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('block_name')
            ->fillRecordUsing(function () {
                return;
            })
                ->rules(['required', 'max:255']),
            ImportColumn::make('year')
            ->fillRecordUsing(function () {
                return;
            })
                ->rules(['required', 'in:1,2,3,4']),
            ImportColumn::make('day_of_the_week')
            ->fillRecordUsing(function () {
                return;
            })
                ->rules(['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday']),
            ImportColumn::make('class_start')
            ->fillRecordUsing(function () {
                return;
            })
                ->rules(['required', 'date_format:H:i']),
            ImportColumn::make('class_end')
            ->fillRecordUsing(function () {
                return;
            })
                ->rules(['required', 'date_format:H:i']),
        ];
    }

    public function resolveRecord(): ?Course
    {
        Log::info('Importing course and lab schedule data:', $this->data);

        // Define the rules for validation
        $rules = [
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:255'],
            'instructor_name' => ['required', 'string', 'max:255'],
            'block_name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'in:1,2,3,4'],
            'day_of_the_week' => ['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'class_start' => ['required', 'date_format:H:i'],
            'class_end' => ['required', 'date_format:H:i'],
        ];

        // Validate the data
        $validator = Validator::make($this->data, $rules);

        // Check if validation fails
        if ($validator->fails()) {
            throw new RowImportFailedException(implode(', ', $validator->errors()->all()));
        }

        // Validate instructor name and fetch the instructor_id
        $instructor = User::where('name', $this->data['instructor_name'])
            ->where('role_number', 2) // Ensure the user is an instructor
            ->first();

        if (!$instructor) {
            throw new RowImportFailedException("Instructor '{$this->data['instructor_name']}' not found.");
        }

        // Validate block name and get the block_id
        $block = Block::where('block', $this->data['block_name'])->first();
        if (!$block) {
            throw new RowImportFailedException("Block '{$this->data['block_name']}' not found.");
        }

        // Validate course doesn't already exist
        $course = Course::where('course_code', $this->data['course_code'])->first();
        if (!$course) {
            // Automatically associate the course with the current on-going year and semester
            $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

            if (!$onGoingYearAndSemester) {
                throw new RowImportFailedException("No active year and semester found. Please ensure an 'on-going' status exists.");
            }

            // Create the course record
            $course = Course::create([
                'course_name' => $this->data['course_name'],
                'course_code' => $this->data['course_code'],
                'instructor_id' => $instructor->id, // Assign instructor_id
                'year_and_semester_id' => $onGoingYearAndSemester->id,
            ]);
        }

        // Validate that class_end is after class_start
        if (strtotime($this->data['class_end']) <= strtotime($this->data['class_start'])) {
            throw new RowImportFailedException('Class end time must be after class start time.');
        }

        // Check for conflicting lab schedule
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
        LabSchedule::create([
            'course_code'    => $this->data['course_code'],
            'course_name'    => $this->data['course_name'],
            'course_id'      => $course->id,
            'instructor_id'  => $instructor->id, // Use the instructor ID here
            'block_id'       => $block->id, // Use the block ID here
            'year'           => $this->data['year'],
            'day_of_the_week' => $this->data['day_of_the_week'],
            'class_start'    => $this->data['class_start'],
            'class_end'      => $this->data['class_end'],
        ]);

        return $course;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course and lab schedule import has completed with ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' successfully imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
