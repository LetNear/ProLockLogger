<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditingAuditable;

class Role extends Model implements Auditable{

    protected $fillable = [
        'name',
        'category',
    ];
    
    use HasFactory;
    use AuditingAuditable;
}
