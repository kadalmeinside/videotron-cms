<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Videotron;
use Illuminate\Auth\Access\Response;

class VideotronPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_videotrons');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Videotron $videotron): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_videotrons');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Videotron $videotron): bool
    {
        return $user->can('manage_videotrons');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Videotron $videotron): bool
    {
        return $user->can('manage_videotrons');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Videotron $videotron): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Videotron $videotron): bool
    {
        return false;
    }
}
