<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function userInformations(): BelongsToMany
    {
        return $this->belongsToMany(UserInformation::class, 'course_user_information', 'course_id', 'user_information_id')
            ->withPivot('schedule_id') // Include schedule_id
            ->withTimestamps();
    }

    

    public function students()
    {
        return $this->belongsToMany(UserInformation::class, 'course_user_information', 'course_id', 'user_information_id');
    }
}
