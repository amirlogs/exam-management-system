<?php

namespace App\Services\Navigation;

use App\Models\User;
use InvalidArgumentException;

class NavigationService
{
    public function forUser(User $user, string $workspace): array
    {
        $items = $this->getWorkspaceItems($workspace);

        return $this->filterItems($items, $user);
    }

    private function getWorkspaceItems(string $workspace): array
    {
        return match ($workspace) {
            'admin' => AdminNavigation::items(),
            'instructor' => InstructorNavigation::items(),
            'student' => StudentNavigation::items(),
            
            default => throw new InvalidArgumentException(
                "Unsupported workspace: {$workspace}"
            ),
        };
    }

    private function filterItems(array $items, User $user): array
    {
        $result = [];

        foreach ($items as $item) {
            if ($this->canSeeItem($item, $user)) {
                $result[] = $this->cleanItem($item);
            }
        }

        return $result;
    }

    private function canSeeItem(array $item, User $user): bool
    {
        $permissions = $item['permissions'] ?? [];

        if (empty($permissions)) {
            return true;
        }

        $mode = $item['permission_mode'] ?? 'any';

        if ($mode === 'all') {
            foreach ($permissions as $permission) {
                if (! $user->hasPermission($permission)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private function cleanItem(array $item): array
    {
        unset(
            $item['permissions'],
            $item['permission_mode']
        );

        return $item;
    }
}
