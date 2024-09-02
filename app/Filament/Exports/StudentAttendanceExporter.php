<?php

namespace App\Filament\Exports;

use App\Models\StudentAttendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StudentAttendanceExporter extends Exporter
{
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
                ->label('Student Number'),

            ExportColumn::make('name')
                ->label('Name'),

            ExportColumn::make('course')
                ->label('Course'),

            ExportColumn::make('year')
                ->label('Year Level'),

            ExportColumn::make('block')
                ->label('Block'),

            ExportColumn::make('time_in')
                ->label('Time In'),

            ExportColumn::make('time_out')
                ->label('Time Out'),

            ExportColumn::make('status')
                ->label('Status'),

            ExportColumn::make('created_at')
                ->label('Created At'),

            ExportColumn::make('updated_at')
                ->label('Updated At'),
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
