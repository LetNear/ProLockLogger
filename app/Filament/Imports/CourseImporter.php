<?php

namespace App\Filament\Imports;

use App\Models\Course;
use App\Models\YearAndSemester;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Validator;

class CourseImporter extends Importer
{
    protected static ?string $model = Course::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('course_name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('course_code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('course_description')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('instructor_name')
                ->fillRecordUsing(function () {
                    return;
                })
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Course
    {
        // Define the rules for validation
        $rules = [
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:255'],
            'course_description' => ['required', 'string', 'max:255'],
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
            throw new RowImportFailedException('Instructor not found');
        }

        // Automatically associate the course with the current on-going year and semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

        if (!$onGoingYearAndSemester) {
            throw new RowImportFailedException("No active year and semester found. Please ensure an 'on-going' status exists.");
        }

        // Check for duplicates in the same year and semester
        $duplicateCourse = Course::where('course_name', $this->data['course_name'])
            ->where('course_code', $this->data['course_code'])
            ->where('instructor_id', $instructor->id)
            ->where('year_and_semester_id', $onGoingYearAndSemester->id)
            ->exists();

        if ($duplicateCourse) {
            // Throw an exception with a detailed duplicate course message
            throw new RowImportFailedException("Duplicate course detected: {$this->data['course_name']} with {$this->data['course_code']} for Instructor {$this->data['instructor_name']} already exists in this year and semester. Please add it manually.");
        }

        // Proceed to insert the course record
        return Course::create([
            'course_name' => $this->data['course_name'],
            'course_code' => $this->data['course_code'],
            'course_description' => $this->data['course_description'],
            'instructor_id' => $instructor->id, // Assign instructor_id
            'year_and_semester_id' => $onGoingYearAndSemester->id,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' were imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import due to duplicate entries.';
        }

        return $body;
    }
}
