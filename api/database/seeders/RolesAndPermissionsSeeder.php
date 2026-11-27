<?php

namespace Database\Seeders;

use App\Enums\OrganizationPermission;
use App\Enums\OrganizationRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the organization roles and permissions from the enums.
 *
 * Roles are global (organization_id = null), so the same "manager" role can be
 * assigned in any organization; the assignment itself is scoped by organization_id.
 * Safe to run again: it syncs instead of duplicating.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        setPermissionsTeamId(null);

        foreach (OrganizationPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // spatie caches the permission list on first lookup and normally clears it when a
        // permission is saved. DatabaseSeeder uses WithoutModelEvents, which disables that,
        // so the cache would miss the permissions created above: clear it ourselves and
        // pass models instead of names to syncPermissions().
        $registrar->forgetCachedPermissions();

        foreach (OrganizationRole::cases() as $role) {
            $names = array_map(fn (OrganizationPermission $p) => $p->value, $role->permissions());

            Role::findOrCreate($role->value)->syncPermissions(Permission::whereIn('name', $names)->get());
        }

        $registrar->forgetCachedPermissions();
    }
}
