<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class Seat extends Model implements Auditable
{

    protected $fillable = [
        'computer_number',
        'instructor',
        'year_section',
        'lab_attendance_id',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    use HasFactory;
    use AuditingAuditable;
}
