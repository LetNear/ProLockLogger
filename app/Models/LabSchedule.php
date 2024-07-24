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
        'year_and_program_id',
        'subject_code',
        'subject_name',
        'instructor',
        'day_of_the_week',
        'class_start',
        'class_end',
        
    ];
}
