<?php

namespace App\Filament\Exports;

use App\Models\LabAttendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LabAttendanceExporter extends Exporter
{
    protected static ?string $model = LabAttendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('instructor'),
            ExportColumn::make('time_in'),
            ExportColumn::make('time_out'),
            ExportColumn::make('status'),
            ExportColumn::make('logdate'),
            // ExportColumn::make('created_at'),
            // ExportColumn::make('updated_at'),
        ];
    }

    // Override the query to filter data where role_number is 2
    protected function getQuery()
    {
        return LabAttendance::query()->whereHas('user', function($query) {
            $query->where('role_number', 2);
        });
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
