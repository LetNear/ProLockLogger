<?php

namespace App\Filament\Exports;

use App\Models\LabSchedule;
use App\Models\StudentAttendance;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class StudentAttendanceExporter extends Exporter
{
    protected static ?string $model = StudentAttendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('userInformation.user.name')->label('Name'),
            ExportColumn::make('userInformation.courses.course_name')->label('Course'),
            ExportColumn::make('userInformation.year')->label('Year'),
            ExportColumn::make('userInformation.block.block')->label('Block'),
            ExportColumn::make('userInformation.user_number')->label('Student Number'),
            ExportColumn::make('time_in'),
            ExportColumn::make('time_out'),
            ExportColumn::make('status'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user->role_number === 2) {
            $query->whereHas('userInformation', function (Builder $query) use ($user) {
                $query->whereHas('courses', function (Builder $query) use ($user) {
                    $query->whereHas('labSchedules', function (Builder $query) use ($user) {
                        $query->where('instructor_id', $user->id);
                    });
                });
            });
        }

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your student attendance export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
