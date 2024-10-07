<?php

namespace App\Filament\Resources\YearAndSemesterResource\Pages;

use App\Filament\Resources\YearAndSemesterResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use Filament\Actions;
use App\Models\UserInformation;
use App\Models\YearAndSemester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditYearAndSemester extends EditRecord
{
    protected static string $resource = YearAndSemesterResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if($data['status'] == 'on-going'){
            YearAndSemester::query()->where('status', 'on-going')->update(['status' => 'closed']);
            $this->duplicateDataOfUsers($record);
        }

        else if($record->status == 'pending'){
            // Do nothing
        }

        $record->update($data);

        return $record;
    }

    protected function duplicateDataOfUsers($record) {
        // Keep the current year and semester in a variable
        $yearAndSemester = $record;
    
        // Update the year and semester for users whose role_number is not 1
        $users = User::query()
            ->whereNot('role_number', 1)
            ->update(['year_and_semester_id' => $record->id]);
    
        // Loop through all users who are not role_number 1
        $usersToProcess = User::whereNot('role_number', 1)->get();
    
        foreach ($usersToProcess as $user) {
            // First, check if the record already exists for the current year and semester
            $existingRecord = UserInformation::where('user_id', $user->id)
                ->where('year_and_semester_id', $yearAndSemester->id)
                ->first();
    
            // If no existing record, and user year is less than 4, duplicate the data
            if (!$existingRecord) {
                // Get the user information you want to duplicate
                $userInformation = UserInformation::where('user_id', $user->id)->first();
    
                // Proceed only if the year is less than 4
                if ($userInformation && $userInformation->year < 4) {
                    // Duplicate the data and reset courses
                    $newUserInformation = $userInformation->replicate(); // Duplicate the user info
                    $newUserInformation->id = null; // Set the ID to null to let it auto-increment
                    $newUserInformation->year_and_semester_id = $yearAndSemester->id; // Set the new year and semester
                    $newUserInformation->course_id = null; // Drop/clear attached courses
                    
                    // Increment the year by 1
                    $newUserInformation->year += 1;
    
                    // Save the duplicated record with the new unique ID
                    $newUserInformation->save();
                }
            }
        }
    }
    
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

   
}
