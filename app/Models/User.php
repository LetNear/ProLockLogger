<?php

namespace App\Models;

use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Notifications\Notification;

class User extends Authenticatable implements Auditable, FilamentUser
{
    use HasFactory, Notifiable;
    use AuditingAuditable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role_number',
        'fingerprint_id',
        'year_and_semester_id',
        'is_protected',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'fingerprint_id' => 'array',  // Correctly cast fingerprint_id as an array
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_number');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Administrator', 'Faculty']);
    }

    public function getNameAttribute(): string
    {
        // Assuming the name attribute exists in the User model
        return $this->attributes['name'];
    }


    public function getFingerprintIdAttribute($value)
    {
        // Decode if it's a JSON string, otherwise return an empty array
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function setFingerprintIdAttribute($value)
    {
        // Encode array to JSON string for storage
        $this->attributes['fingerprint_id'] = is_array($value) ? json_encode($value) : $value;
    }

    public function yearAndSemester()
    {
        return $this->belongsTo(YearAndSemester::class, 'year_and_semester_id');
    }


    public static function boot()
    {
        parent::boot();

        // Automatically set the active year and semester on creating
        static::creating(function ($user) {
            $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();

            if ($onGoingYearAndSemester) {
                $user->year_and_semester_id = $onGoingYearAndSemester->id;
            }
        });
    }

    // In User model or a service layer
    public function delete()
    {
        // Check if the user is protected from deletion
        if ($this->is_protected) {
            // Show a notification using Filament's notification system
            Notification::make()
                ->title('Deletion Error')
                ->body('This user cannot be deleted because they are protected.')
                ->danger()
                ->send();

            return false; // Indicate that the deletion did not proceed
        }

        return parent::delete();
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }
}
