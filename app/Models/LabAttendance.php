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

    public function recentLog()
    {
        return $this->belongsTo(RecentLogs::class, 'user_id', 'user_number');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function labSchedule()
    {
        return $this->belongsTo(LabSchedule::class);
    }
}
