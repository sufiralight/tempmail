<?php

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function manage(User $actor, User $target): bool
    {
        return $actor->can('users.manage') && $actor->hasVerifiedEmail();
    }
}
