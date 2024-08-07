<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class LabAttendance extends Model implements Auditable
{
    use HasFactory;
    use AuditingAuditable;

    protected $fillable = [
        'user_id',
        'seat_id',
        'lab_schedule_id',
        'time_in',
        'time_out',
        'status',
        'logdate',
        'instructor',
    ];
}
