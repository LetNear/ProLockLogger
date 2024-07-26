<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NfcTag extends Model
{
    use HasFactory;

    protected $fillable = ['rfid_number'];
}
