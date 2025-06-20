<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'view dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage roles', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage permissions', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_videotrons', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_media', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_playlists', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'approve_media', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_own_client_data', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_content', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schedules', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage application settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_reports', 'guard_name' => 'web']);
        
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $managerRole = Role::create(['name' => 'content-manager']);
        $managerRole->givePermissionTo(['manage_content', 'manage_schedules', 'view_reports', 'approve_media']);

        $clientRole = Role::create(['name' => 'client']);
        $clientRole->givePermissionTo(['view reports']);

        $this->command->info('Roles and Permissions seeded successfully!');
    }
}
