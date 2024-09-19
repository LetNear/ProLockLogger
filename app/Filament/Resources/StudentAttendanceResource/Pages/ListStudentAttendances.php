<?php

namespace App\Filament\Resources\StudentAttendanceResource\Pages;

use App\Filament\Resources\StudentAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentAttendances extends ListRecords
{
    protected static string $resource = StudentAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Keep this commented if you don't want the create action
        ];
    }

    // Override the getTableQuery method to apply custom logic for the logged-in instructor
    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        // Get the logged-in user
        $user = auth()->user();
    
        // Check the user's role_number
        if ($user->role_number == 1) {
            // If the user has a role_number of 1, show all the data
            return $query;
        } elseif ($user->role_number == 2) {
            // If the user has a role_number of 2, filter records by the instructor's specific lab schedules and courses
            return $query->whereHas('userInformation.courses.labSchedules', function ($query) use ($user) {
                // Ensure that the schedule is tied to the correct instructor and that it matches the course they are teaching
                $query->where('instructor_id', $user->id)
                      ->whereColumn('user_information_id', 'course_user_information.user_information_id') // Check that user is linked to the course
                      ->whereNotNull('course_id'); // Ensure the course is assigned
            });
        }
        
    }
}