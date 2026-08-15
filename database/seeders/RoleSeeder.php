<?php

namespace Pilot\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view content',
            'create content',
            'edit content',
            'delete content',
            'publish content',
            'view assets',
            'upload assets',
            'edit assets',
            'delete assets',
            'view datasources',
            'manage datasources',
            'view users',
            'manage users',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::all());

        $editor = Role::firstOrCreate(['name' => 'Editor']);
        $editor->givePermissionTo([
            'view content', 'create content', 'edit content', 'delete content', 'publish content',
            'view assets', 'upload assets', 'edit assets', 'delete assets',
            'view datasources', 'manage datasources',
        ]);

        $author = Role::firstOrCreate(['name' => 'Author']);
        $author->givePermissionTo([
            'view content', 'create content', 'edit content',
            'view assets', 'upload assets',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'Viewer']);
        $viewer->givePermissionTo([
            'view content',
            'view assets',
            'view datasources',
        ]);
    }
}
