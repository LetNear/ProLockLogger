<?php

namespace App\Filament\Resources\YearAndSemesterResource\Pages;

use App\Filament\Resources\YearAndSemesterResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
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

    protected function duplicateDataOfUsers($record){
        $yearAndSemester = $record;

        $users = User::query()->whereNot('role_number', 1)->update(['year_and_semester_id' => $record->id]);
        $userInformations = UserInformation::query()->update(['year_and_semester_id' => $record->id]);


    }
}
