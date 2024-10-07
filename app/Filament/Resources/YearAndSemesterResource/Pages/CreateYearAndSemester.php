<?php

namespace App\Filament\Resources\YearAndSemesterResource\Pages;

use App\Filament\Resources\YearAndSemesterResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Models\YearAndSemester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateYearAndSemester extends CreateRecord
{
    protected static string $resource = YearAndSemesterResource::class;

    // protected function saved(): void
    // {
    //     Log::info('YearAndSemester created. ID: ' . $this->record->id);

    //     // If the status is set to 'on-going', trigger the changeYearAndSemester logic
    //     if ($this->record->status === 'on-going') {
    //         Log::info('YearAndSemester status is on-going. Triggering duplication...');

    //         $this->changeYearAndSemester($this->record->id);
    //     }
    // }

    // protected function changeYearAndSemester($newYearAndSemesterId)
    // {
    //     Log::info('Changing the ongoing year and semester to ID: ' . $newYearAndSemesterId);

    //     // Close the current ongoing year and semester
    //     YearAndSemester::where('status', 'on-going')->update(['status' => 'closed']);
    //     Log::info('Closed current ongoing year and semester.');

    //     // Set the new year and semester as ongoing
    //     $newYearAndSemester = YearAndSemester::find($newYearAndSemesterId);
    //     $newYearAndSemester->status = 'on-going';
    //     $newYearAndSemester->save();

    //     Log::info('New YearAndSemester is now ongoing.');

    //     // Start the duplication process
    //     $this->duplicateDataForNewYearAndSemester($newYearAndSemesterId);
    // }

    // protected function duplicateDataForNewYearAndSemester($newYearAndSemesterId)
    // {
    //     Log::info('Starting duplication for YearAndSemester ID: ' . $newYearAndSemesterId);

    //     $currentYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

    //     if (!$currentYearAndSemester) {
    //         Log::error('No ongoing year and semester found.');
    //         return;
    //     }

    //     Log::info('Current ongoing Year and Semester ID: ' . $currentYearAndSemester->id);

    //     $users = User::where('year_and_semester_id', $currentYearAndSemester->id)->get();
    //     Log::info('Found ' . $users->count() . ' users to duplicate.');

    //     if ($users->isEmpty()) {
    //         Log::info('No users found for current year and semester.');
    //         return;
    //     }

    //     DB::transaction(function () use ($users, $newYearAndSemesterId) {
    //         foreach ($users as $user) {
    //             $newUser = $user->replicate(); // Duplicate the user
    //             $newUser->year_and_semester_id = $newYearAndSemesterId;
    //             $newUser->save(); // Save the duplicated user with a new ID

    //             Log::info('Duplicated user ID ' . $user->id . ' to new user ID ' . $newUser->id);
    //         }
    //     });

    //     Log::info('Duplication process completed.');
    // }
}
