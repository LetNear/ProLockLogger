<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditingAuditable;

class RecentLogs extends Model implements Auditable
{
    use HasFactory, AuditingAuditable;

    protected $fillable = [
        'user_number',
        'block_id',
        'role_id',
        'year',
        'time_in',
        'time_out',
        'id_card_id',
        'fingerprint_id',
        'year_and_semester_id',
        'seat_id', // Ensure seat_id is fillable
        'assigned_instructor',
    ];

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    public function nfc()
    {
        return $this->belongsTo(Nfc::class, 'id_card_id');
    }

    public function userInformation()
    {
        return $this->belongsTo(UserInformation::class, 'user_number', 'user_number');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id');
    }

    public function computer()
    {
        return $this->seat->computer(); // Indirectly access through the seat relationship
    }

    public function yearAndSemester()
    {
        return $this->belongsTo(YearAndSemester::class, 'year_and_semester_id');
    }
}
