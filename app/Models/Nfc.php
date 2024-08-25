<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class Nfc extends Model implements Auditable
{
    protected $fillable = [
        'rfid_number',
    ];

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class, 'id_card_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }
    use HasFactory;
    use AuditingAuditable;
}
