<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\YearAndSemester; // Import the YearAndSemester model
use Illuminate\Database\Eloquent\Builder; // Import the Builder class

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

 /**
     * Modify the query to filter students by the current active year and semester, and order them accordingly.
     *
     * @return ?Builder
     */
    protected function getTableQuery(): ?Builder
    {
        // Fetch the active year and semester
        $activeYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

        // Call the parent query to start
        $query = parent::getTableQuery();

        // If there is no active year and semester, just order chronologically
        if (!$activeYearAndSemester) {
            return $query->orderBy('year_and_semester_id', 'desc'); // Order by year and semester chronologically
        }

        // If there is an active year and semester, prioritize it first and then order others by year and semester chronologically
        return $query
            ->orderByRaw("CASE WHEN year_and_semester_id = ? THEN 0 ELSE 1 END", [$activeYearAndSemester->id]) // Prioritize current on-going year and semester
            ->orderBy('year_and_semester_id', 'desc'); // Order remaining by year and semester chronologically
    }

    // /**
    //  * Modify the query to filter students by the current active year and semester.
    //  *
    //  * @return ?Builder
    //  */
    // protected function getTableQuery(): ?Builder
    // {
    //     // Call the parent query to start
    //     $query = parent::getTableQuery();

    //     // Fetch the active year and semester
    //     $activeYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

    //     // If there is no active year and semester, return the default query without filtering
    //     if (!$activeYearAndSemester) {
    //         return $query;
    //     }

    //     // Filter students by active year and semester
    //     return $query->where('year_and_semester_id', $activeYearAndSemester->id);
    // }
}
