<?php

namespace App\Enums;

/**
 * Roles a user can have inside an organization (one role per organization).
 *
 * The role → permissions map below is the single source of truth:
 * RolesAndPermissionsSeeder syncs it into spatie's tables.
 */
enum OrganizationRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    /**
     * @return list<OrganizationPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner, self::Manager => OrganizationPermission::cases(),
            self::Staff => [OrganizationPermission::ViewMembers],
        };
    }

    /**
     * Whether a member with this role may give $role to someone, or change/remove
     * a member who currently has $role. Owners manage everyone; managers only staff.
     */
    public function canManage(self $role): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Manager => $role === self::Staff,
            self::Staff => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
