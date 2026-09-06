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
            // If item has nested children, recursively filter them first
            if (!empty($item['children']) && is_array($item['children'])) {
                $filteredChildren = $this->filterItems($item['children'], $user);

                // If user cannot see any of the children, hide this parent group completely
                if (empty($filteredChildren)) {
                    continue;
                }

                // If parent also specifies explicit permissions, verify them
                if (!$this->canSeeItem($item, $user)) {
                    continue;
                }

                $cleaned = $this->cleanItem($item);
                $cleaned['children'] = array_values($filteredChildren);
                $result[] = $cleaned;
            } else {
                if ($this->canSeeItem($item, $user)) {
                    $result[] = $this->cleanItem($item);
                }
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
