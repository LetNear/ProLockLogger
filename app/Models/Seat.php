<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class Seat extends Model implements Auditable
{

    protected $fillable = [
        'computer_id',
        'instructor_name',
        'year_section',
        'lab_attendance_id',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function userInformations()
    {
        return $this->belongsTo(UserInformation::class);
    }

    

    use HasFactory;
    use AuditingAuditable;
}
