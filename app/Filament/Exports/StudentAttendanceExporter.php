<?php

namespace App\Filament\Exports;

use App\Models\LabSchedule;
use App\Models\StudentAttendance;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StudentAttendanceExporter extends Exporter
{
    protected static ?string $model = StudentAttendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('userInformation.user.name')
                ->label('Name'),
            ExportColumn::make('userInformation.year')
                ->label('Year'),
            ExportColumn::make('userInformation.block.block')
                ->label('Block'),
            ExportColumn::make('userInformation.user_number')
                ->label('Student Number'),
            ExportColumn::make('time_in')
                ->label('Time In'), // Using the accessor for time in
            ExportColumn::make('time_out')
                ->label('Time Out'), // Using the accessor for time out
            ExportColumn::make('duration')
                ->label('Duration'), // Using the accessor for duration
            ExportColumn::make('formatted_date')
                ->label('Date'), // Using the accessor for formatted date
            ExportColumn::make('associated_course')
                ->label('Course'), // New column for course name
            ExportColumn::make('class_type')
                ->label('Class Type'), // New column for class type (makeup or regular)
            ExportColumn::make('present_or_absent')
                ->label('Status'), // Using the accessor for Present or Absent
            
        ];
    }
    
    

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();
    
        // Check if the user is an instructor
        if ($user->role_number === 2) {
            // Filter students strictly by courses that the instructor is handling
            $query->whereHas('userInformation.courses', function (Builder $courseQuery) use ($user) {
                $courseQuery->whereHas('labSchedules', function (Builder $scheduleQuery) use ($user) {
                    $scheduleQuery->where('instructor_id', $user->id); // Filter to ensure the course belongs to the instructor
                });
            })->with([
                'userInformation' => function ($userInfoQuery) {
                    $userInfoQuery->with([
                        'user', // Load the user relationship for the name
                        'courses' => function ($courseQuery) {
                            // Only load courses associated with the correct lab schedules
                            $courseQuery->whereHas('labSchedules', function ($scheduleQuery) {
                                $scheduleQuery->whereNotNull('instructor_id'); // Ensure the courses have schedules with instructors
                            })->select('id', 'course_name');
                        },
                        'block' // Load the block details
                    ])->select('id', 'user_id', 'year', 'block_id', 'user_number'); // Select only necessary columns
                }
            ]);
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
