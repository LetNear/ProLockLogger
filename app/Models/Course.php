<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_name',
        'course_code',
        'course_description',
    ];

    public function labSchedules()
    {
        return $this->hasMany(LabSchedule::class);
    }

    public function userInformations()
    {
        return $this->belongsToMany(UserInformation::class, 'course_user_information');
    }
}
