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

    public function seat()
    {
        return $this->hasOne(Seat::class, 'computer_id');
    }
    use HasFactory;
}
