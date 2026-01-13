<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==================== CREAR PERMISOS ====================

        // Permisos de Emails
        Permission::create(['name' => 'emails.view_own']);
        Permission::create(['name' => 'emails.view_shared']);
        Permission::create(['name' => 'emails.view_all']);
        Permission::create(['name' => 'emails.create']);
        Permission::create(['name' => 'emails.edit_own']);
        Permission::create(['name' => 'emails.edit_all']);
        Permission::create(['name' => 'emails.delete_own']);
        Permission::create(['name' => 'emails.delete_all']);
        Permission::create(['name' => 'emails.manage_workflow']);

        // Permisos de Carpetas
        Permission::create(['name' => 'folders.view']);
        Permission::create(['name' => 'folders.create']);
        Permission::create(['name' => 'folders.edit_own']);
        Permission::create(['name' => 'folders.edit_all']);
        Permission::create(['name' => 'folders.delete_own']);
        Permission::create(['name' => 'folders.delete_all']);
        Permission::create(['name' => 'folders.share']);

        // Permisos de Contactos
        Permission::create(['name' => 'contacts.view_own']);
        Permission::create(['name' => 'contacts.view_all']);
        Permission::create(['name' => 'contacts.manage_own']);
        Permission::create(['name' => 'contacts.manage_all']);

        // Permisos de Alertas
        Permission::create(['name' => 'alerts.view_own']);
        Permission::create(['name' => 'alerts.view_all']);
        Permission::create(['name' => 'alerts.manage_own']);
        Permission::create(['name' => 'alerts.manage_all']);

        // Permisos de Templates
        Permission::create(['name' => 'templates.view']);
        Permission::create(['name' => 'templates.create']);
        Permission::create(['name' => 'templates.edit_own']);
        Permission::create(['name' => 'templates.edit_all']);
        Permission::create(['name' => 'templates.delete_own']);
        Permission::create(['name' => 'templates.delete_all']);

        // Permisos de Organización
        Permission::create(['name' => 'organization.view_settings']);
        Permission::create(['name' => 'organization.manage_settings']);
        Permission::create(['name' => 'organization.view_members']);
        Permission::create(['name' => 'organization.invite_members']);
        Permission::create(['name' => 'organization.remove_members']);
        Permission::create(['name' => 'organization.change_member_roles']);
        Permission::create(['name' => 'organization.view_analytics']);
        Permission::create(['name' => 'organization.manage_billing']);

        // ==================== CREAR ROLES CON PERMISOS ====================

        // 1. ORGANIZATION OWNER - Control total
        $ownerRole = Role::create(['name' => 'organization_owner']);
        $ownerRole->givePermissionTo(Permission::all());

        // 2. ADMIN - Gestión de organización (casi todo excepto billing)
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            // Emails
            'emails.view_all',
            'emails.create',
            'emails.edit_all',
            'emails.delete_all',
            'emails.manage_workflow',
            // Carpetas
            'folders.view',
            'folders.create',
            'folders.edit_all',
            'folders.delete_all',
            'folders.share',
            // Contactos
            'contacts.view_all',
            'contacts.manage_all',
            // Alertas
            'alerts.view_all',
            'alerts.manage_all',
            // Templates
            'templates.view',
            'templates.create',
            'templates.edit_all',
            'templates.delete_all',
            // Organización
            'organization.view_settings',
            'organization.manage_settings',
            'organization.view_members',
            'organization.invite_members',
            'organization.remove_members',
            'organization.change_member_roles',
            'organization.view_analytics',
        ]);

        // 3. MANAGER - Gestión de equipo
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo([
            // Emails
            'emails.view_shared',
            'emails.view_all',
            'emails.create',
            'emails.edit_all',
            'emails.manage_workflow',
            // Carpetas
            'folders.view',
            'folders.create',
            'folders.edit_all',
            'folders.share',
            // Contactos
            'contacts.view_all',
            'contacts.manage_all',
            // Alertas
            'alerts.view_all',
            'alerts.manage_all',
            // Templates
            'templates.view',
            'templates.create',
            'templates.edit_all',
            // Organización
            'organization.view_settings',
            'organization.view_members',
            'organization.view_analytics',
        ]);

        // 4. SECRETARY - Gestión de emails del jefe
        $secretaryRole = Role::create(['name' => 'secretary']);
        $secretaryRole->givePermissionTo([
            // Emails
            'emails.view_shared',
            'emails.create',
            'emails.edit_own',
            'emails.manage_workflow',
            // Carpetas
            'folders.view',
            'folders.create',
            'folders.edit_own',
            // Contactos
            'contacts.view_all',
            'contacts.manage_own',
            // Alertas
            'alerts.view_own',
            'alerts.manage_own',
            // Templates
            'templates.view',
            'templates.create',
            'templates.edit_own',
            // Organización
            'organization.view_members',
        ]);

        // 5. ADMINISTRATIVE - Operativo limitado
        $administrativeRole = Role::create(['name' => 'administrative']);
        $administrativeRole->givePermissionTo([
            // Emails
            'emails.view_own',
            'emails.view_shared',
            'emails.create',
            'emails.edit_own',
            // Carpetas
            'folders.view',
            'folders.create',
            'folders.edit_own',
            // Contactos
            'contacts.view_own',
            'contacts.manage_own',
            // Alertas
            'alerts.view_own',
            // Templates
            'templates.view',
            // Organización
            'organization.view_members',
        ]);

        // 6. EMPLOYEE - Solo emails propios
        $employeeRole = Role::create(['name' => 'employee']);
        $employeeRole->givePermissionTo([
            // Emails
            'emails.view_own',
            'emails.create',
            'emails.edit_own',
            'emails.delete_own',
            // Carpetas
            'folders.view',
            'folders.create',
            'folders.edit_own',
            'folders.delete_own',
            // Contactos
            'contacts.view_own',
            'contacts.manage_own',
            // Alertas
            'alerts.view_own',
            // Templates
            'templates.view',
        ]);

        $this->command->info('✅ Roles y permisos creados exitosamente!');
        $this->command->info('');
        $this->command->info('Roles creados:');
        $this->command->info('  1. organization_owner - Control total (' . $ownerRole->permissions->count() . ' permisos)');
        $this->command->info('  2. admin - Gestión de organización (' . $adminRole->permissions->count() . ' permisos)');
        $this->command->info('  3. manager - Gestión de equipo (' . $managerRole->permissions->count() . ' permisos)');
        $this->command->info('  4. secretary - Gestión de emails del jefe (' . $secretaryRole->permissions->count() . ' permisos)');
        $this->command->info('  5. administrative - Operativo limitado (' . $administrativeRole->permissions->count() . ' permisos)');
        $this->command->info('  6. employee - Solo emails propios (' . $employeeRole->permissions->count() . ' permisos)');
    }
}
