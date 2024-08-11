<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditingAuditable;
use OwenIt\Auditing\Contracts\Auditable;

class LabSchedule extends Model implements Auditable
{
    use HasFactory;
    use AuditingAuditable;

    protected $fillable = [
        
        'subject_code',
        'subject_name',
        'instructor_name',
        'block_id',
        'instructor_id',
        'year',
        'day_of_the_week',
        'class_start',
        'class_end',
        
    ];

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function userInformation()
    {
        return $this->belongsTo(UserInformation::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
