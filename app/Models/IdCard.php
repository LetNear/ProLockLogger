<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class IdCard extends Model implements Auditable
{

    protected $fillable = [
        'image_id',
        'rfid_number',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }
    use HasFactory;
    use AuditingAuditable;
}
