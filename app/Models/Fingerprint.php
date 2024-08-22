<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    protected $table = 'fingerprint';

    protected $fillable = ['name', 'fingerprint'];
    use HasFactory;
}
