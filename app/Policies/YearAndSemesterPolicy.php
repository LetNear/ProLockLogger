<?php

namespace App\Policies;

use App\Models\User;
use App\Models\YearAndSemester;
use Illuminate\Auth\Access\Response;

class YearAndSemesterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // return $user->hasAnyRole(['Administrator', 'Faculty']);
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, YearAndSemester $yearAndSemester): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, YearAndSemester $yearAndSemester): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, YearAndSemester $yearAndSemester): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, YearAndSemester $yearAndSemester): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, YearAndSemester $yearAndSemester): bool
    {
        return $user->hasRole('Administrator');
    }
}
