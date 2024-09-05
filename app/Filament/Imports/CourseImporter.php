<?php

namespace App\Filament\Imports;

use App\Models\Course;
use App\Models\YearAndSemester;
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
        ];
    }

    public function resolveRecord(): ?Course
    {
        // Define the rules for validation
        $rules = [
            'course_name' => ['required', 'string', 'max:255', 'unique:courses,course_name'],
            'course_code' => ['required', 'string', 'max:255', 'unique:courses,course_code'],
            'course_description' => ['required', 'string', 'max:255'],
        ];

        // Validate the data
        $validator = Validator::make($this->data, $rules);

        // Check if validation fails
        if ($validator->fails()) {
            throw new RowImportFailedException(implode(', ', $validator->errors()->all()));
        }

        // Automatically associate the course with the current on-going year and semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

        if (!$onGoingYearAndSemester) {
            throw new RowImportFailedException("No active year and semester found. Please ensure an 'on-going' status exists.");
        }

        // Create a new course record or update an existing one if conflicts are managed
        return new Course([
            'course_name' => $this->data['course_name'],
            'course_code' => $this->data['course_code'],
            'course_description' => $this->data['course_description'],
            'year_and_semester_id' => $onGoingYearAndSemester->id,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
