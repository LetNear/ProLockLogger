<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    protected $fillable = [
        'computer_number',
        'brand',
        'model',
        'serial_number',
    ];
    use HasFactory;
}
