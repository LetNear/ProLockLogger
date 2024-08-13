<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // determines if the navigation resource is shown.
        return $user->hasAnyRole(['Administrator', 'Faculty']);
        return $user->hasRole('Administrator');
        // return $user->hasRole('Instructor');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // determines if the user information can be viewed.
        // return $user->hasAnyRole(['Administrator' ]);
        // return $user->hasRole('Administrator');
        return $user->hasAnyRole(['Administrator', 'Faculty']);

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // return $user->hasRole('Administrator');
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // return $user->hasAnyRole(['Administrator', 'Student']);
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // return $user->hasRole('Administrator');
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // return $user->hasRole('Administrator');
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // return $user->hasRole('Administrator');
        return $user->hasRole('Administrator');
    }
}
