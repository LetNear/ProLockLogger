<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_information_id',
        'time_in',
        'time_out',
        'status',
    ];

    public function userInformation()
    {
        return $this->belongsTo(UserInformation::class);
    }
}
