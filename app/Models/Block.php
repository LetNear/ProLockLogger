<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'block',
    ];
    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    public function labSchedules()
    {
        return $this->hasMany(LabSchedule::class);
    }
    public function seats()
    {
        return $this->hasOne(Seat::class);
    }
    
}
