<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\Garden;
use App\Models\User;

class AreaPolicy
{
    private function isOwner(User $user, Area $area): bool
    {
        return $user->id === $area->garden->user_id;
    }

    public function view(User $user, Area $area): bool
    {
        return $this->isOwner($user, $area);
    }

    public function create(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function update(User $user, Area $area): bool
    {
        return $this->isOwner($user, $area);
    }

    public function archive(User $user, Area $area): bool
    {
        return $this->isOwner($user, $area);
    }

    public function unarchive(User $user, Area $area): bool
    {
        return $this->isOwner($user, $area);
    }

    public function delete(User $user, Area $area): bool
    {
        return $this->isOwner($user, $area);
    }
}
