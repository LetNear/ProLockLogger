<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

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
}
