<?php

namespace App\Filament\Exports;

use App\Models\StudentAttendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Concerns\WithColumns; // Import WithColumns trait

class StudentAttendanceExporter extends Exporter
{
    use WithColumns; // Use the trait to handle columns

    protected static ?string $model = StudentAttendance::class;

    /**
     * Define the columns to be exported.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student_number')
                ->label('Student Number')
                ->description('Unique identifier for the student'),

            ExportColumn::make('name')
                ->label('Name')
                ->description('Full name of the student'),

            ExportColumn::make('course')
                ->label('Course')
                ->description('Course the student is enrolled in'),

            ExportColumn::make('year')
                ->label('Year Level')
                ->description('Year level of the student'),

            ExportColumn::make('block')
                ->label('Block')
                ->description('Block of the student'),

            ExportColumn::make('time_in')
                ->label('Time In')
                ->description('Time the student checked in'),

            ExportColumn::make('time_out')
                ->label('Time Out')
                ->description('Time the student checked out'),

            ExportColumn::make('status')
                ->label('Status')
                ->description('Attendance status (Present, Absent, Late)'),

            ExportColumn::make('created_at')
                ->label('Created At')
                ->description('Record creation timestamp'),

            ExportColumn::make('updated_at')
                ->label('Updated At')
                ->description('Last updated timestamp'),
        ];
    }

    /**
     * Get the notification body for completed export.
     *
     * @param Export $export
     * @return string
     */
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your student attendance export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
