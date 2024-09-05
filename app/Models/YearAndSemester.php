<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearAndSemester extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year',
        'semester',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get the active year and semester records.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Set the specified YearAndSemester record as active and others as inactive.
     *
     * @param int $id
     * @return void
     */
    public static function setActive($id)
    {
        // Begin a database transaction
        \DB::transaction(function () use ($id) {
            // Set all other records to inactive
            self::where('status', 'active')->update(['status' => 'inactive']);

            // Set the selected record as active
            self::where('id', $id)->update(['status' => 'active']);
        });
    }
}
