<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class UserService
{
    /**
     * Get the first leader for a user based on their hierarchy list.
     *
     * @param string|null $hierarchyList The hierarchy list string (e.g., "-2-3-4-").
     * @param Collection $leaders Preloaded collection of potential leaders.
     * @return User|null
     */
    public function getFirstLeader(?string $hierarchyList, Collection $leaders): ?User
    {
        if (!$hierarchyList) {
            return null;
        }

        // Parse the hierarchy list into an array of user IDs
        $upline = explode('-', trim($hierarchyList, '-'));
        foreach (array_reverse($upline) as $userId) {
            if ($leaders->has($userId)) {
                return $leaders->get($userId);
            }
        }

        return null;
    }
}
