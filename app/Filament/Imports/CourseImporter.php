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
            ImportColumn::make('instructor_name')
            ->fillRecordUsing(function ($record, $state) {
                return;
            })
                ->requiredMapping() // Ensure instructor_name is mapped and imported
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Course
    {
        // Define the rules for validation
        $rules = [
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:255'],
            'instructor_name' => ['required', 'string', 'max:255'], // Validate instructor_name
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

        // If instructor is not found, throw a RowImportFailedException
        if (!$instructor) {
            throw new RowImportFailedException("Instructor '{$this->data['instructor_name']}' not found.");
        }

        // Automatically associate the course with the current on-going year and semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

        if (!$onGoingYearAndSemester) {
            throw new RowImportFailedException("No active year and semester found. Please ensure an 'on-going' status exists.");
        }

        // Proceed to insert the course record
        return Course::create([
            'course_name' => $this->data['course_name'],
            'course_code' => $this->data['course_code'],
            'instructor_id' => $instructor->id, // Assign instructor_id
            'year_and_semester_id' => $onGoingYearAndSemester->id,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' were imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import due to invalid entries.';
        }

        return $body;
    }
}
