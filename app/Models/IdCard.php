<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdCard extends Model
{

    protected $fillable = [
        'image_id',
        'rfid_number',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }
    use HasFactory;
}
