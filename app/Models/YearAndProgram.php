<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearAndProgram extends Model
{

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }
    use HasFactory;
}
