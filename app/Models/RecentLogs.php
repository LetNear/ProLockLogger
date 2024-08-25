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
        'user_id',
        'role_id',
        'block_id',
        'year',
        'time_in',
        'time_out',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id');
    }
    public function userInformation()
    {
        return $this->belongsTo(UserInformation::class, 'user_id');
    }
}
