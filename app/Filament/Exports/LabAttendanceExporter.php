<?php

namespace App\Filament\Exports;

use App\Models\LabAttendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class LabAttendanceExporter extends Exporter
{
    protected static ?string $model = LabAttendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('instructor')->label('Instructor'),
            ExportColumn::make('logdate')->label(now()->toDateString()), // Display the current date as the label
        ];
    }

    // Override the query to filter the latest data for each instructor (role_number 2)
    protected function getQuery(): Builder
    {
        return LabAttendance::query()
            ->whereHas('user', function ($query) {
                $query->where('role_number', 2);  // Filter by role number 2 (instructors)
            })
            ->orderBy('logdate', 'desc') // Sort by the most recent logdate
            ->distinct('instructor_id'); // Only get the latest log per instructor
    }

    protected function mapRecord($record): array
    {
        // Set up to display the instructor's name only on the first row and 'Present' for subsequent rows
        return [
            'instructor' => $this->shouldDisplayInstructor($record) ? $record->user->name : '',
            'logdate' => 'Present', // Display 'Present' for the log date
        ];
    }

    private function shouldDisplayInstructor($record): bool
    {
        static $lastInstructor = null;

        if ($lastInstructor === $record->user->name) {
            return false;  // Don't show the instructor's name again
        }

        $lastInstructor = $record->user->name;
        return true;  // Show the instructor's name for the first row
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your lab attendance export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
