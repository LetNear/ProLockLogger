<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class Role extends Model implements Auditable
{

    protected $fillable = [
        'role',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }
    use HasFactory;
    use AuditingAuditable;
}
