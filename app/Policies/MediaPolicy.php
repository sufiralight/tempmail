<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

final class MediaPolicy
{
    public function view(User $user, Media $media): bool
    {
        return $user->is_active && (int) $media->user_id === (int) $user->id;
    }
}
