<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class UserInformation extends Model implements Auditable
{
    use HasFactory;
    use AuditingAuditable;

    protected $fillable = [
        'user_id',
        'id_card_id',
        'role',
        'seat_id',
        'year',
        'user_number',
       
        'block_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'contact_number',
        'complete_address',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function idCard()
    {
        return $this->belongsTo(Nfc::class, 'id_card_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }


    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function seats()
    {
        return $this->hasOne(Seat::class);
    }

    public function labSchedules()
    {
        return $this->hasMany(LabSchedule::class);
    }
}
