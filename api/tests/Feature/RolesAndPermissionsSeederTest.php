<?php

use App\Enums\OrganizationRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Regression: DatabaseSeeder uses WithoutModelEvents, which also disables the event
 * spatie uses to clear its permission cache. The roles seeder then failed half way,
 * leaving roles without permissions (every permission check returned 403).
 */
it('seeds every role with its permissions through DatabaseSeeder, and can run again', function () {
    DB::table('role_has_permissions')->delete();
    DB::table('roles')->delete();
    DB::table('permissions')->delete();

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    foreach (OrganizationRole::cases() as $role) {
        expect(Role::findByName($role->value)->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($role->permissions())->pluck('value')->sort()->values()->all());
    }
});
