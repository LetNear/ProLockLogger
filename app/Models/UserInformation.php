<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship with the NFC model
    public function idCard()
    {
        return $this->belongsTo(Nfc::class, 'id_card_id');
    }

    // Define the relationship with the Role model
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Define the relationship with the Seat model
    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    // Define the relationship with the Block model
    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    // Define the relationship with the Seat model (alternative)
    public function seats()
    {
        return $this->hasOne(Seat::class);
    }

    // Define the relationship with the LabSchedule model
    public function labSchedules()
    {
        return $this->hasMany(LabSchedule::class);
    }

    // Define the many-to-many relationship with Course model
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user_information', 'user_information_id', 'course_id')
            ->withPivot('schedule_id') // Include schedule_id in the relationship
            ->withTimestamps();
    }
}
