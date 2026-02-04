<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Sistema simplificado de 3 roles para multi-tenancy:
     * - Owner: Control total de la organización
     * - Admin: Co-administrador (gestiona recursos, no billing)
     * - Member: Usuario normal con acceso a recursos compartidos
     */
    public function run(): void
    {
        // Resetear cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('🚀 Creando sistema de permisos simplificado...');
        $this->command->newLine();

        // ==================== CREAR PERMISOS ====================

        $this->command->info('📝 Creando permisos...');

        // === PERMISOS DE ORGANIZACIÓN ===
        $organizationPermissions = [
            'organization.view' => 'Ver detalles de la organización',
            'organization.update' => 'Actualizar configuración de la organización',
            'organization.delete' => 'Eliminar organización',
            'organization.manage_billing' => 'Gestionar facturación (Stripe)',
            'organization.view_members' => 'Ver lista de miembros',
            'organization.invite_members' => 'Invitar nuevos miembros',
            'organization.remove_members' => 'Remover miembros',
            'organization.change_roles' => 'Cambiar roles de miembros',
        ];

        // === PERMISOS DE RECURSOS COMPARTIDOS ===
        $sharedResourcesPermissions = [
            // Carpetas compartidas
            'shared.folders.view' => 'Ver carpetas compartidas de la organización',
            'shared.folders.create' => 'Crear carpetas compartidas',
            'shared.folders.update' => 'Editar carpetas compartidas',
            'shared.folders.delete' => 'Eliminar carpetas compartidas',

            // Templates compartidos
            'shared.templates.view' => 'Ver templates compartidos de la organización',
            'shared.templates.create' => 'Crear templates compartidos',
            'shared.templates.update' => 'Editar templates compartidos',
            'shared.templates.delete' => 'Eliminar templates compartidos',

            // Contactos compartidos
            'shared.contacts.view' => 'Ver contactos compartidos de la organización',
            'shared.contacts.create' => 'Crear contactos compartidos',
            'shared.contacts.update' => 'Editar contactos compartidos',
            'shared.contacts.delete' => 'Eliminar contactos compartidos',
        ];

        // === PERMISOS DE RECURSOS PROPIOS ===
        // Nota: Cada usuario SIEMPRE puede gestionar SUS propios recursos (emails, carpetas, templates, contactos)
        // Estos permisos no se asignan a roles, son implícitos por ser el propietario

        // Crear todos los permisos
        $allPermissions = array_merge($organizationPermissions, $sharedResourcesPermissions);

        foreach ($allPermissions as $permissionName => $description) {
            Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info("✅ {$this->countPermissions($allPermissions)} permisos creados");
        $this->command->newLine();

        // ==================== CREAR ROLES CON PERMISOS ====================

        $this->command->info('👥 Creando roles...');
        $this->command->newLine();

        // === 1. OWNER (PROPIETARIO) 👑 ===
        $this->command->info('  👑 Creando rol: Owner (Propietario)');

        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->givePermissionTo(Permission::all()); // TODOS los permisos

        $this->command->info("     ✅ {$ownerRole->permissions->count()} permisos asignados");
        $this->command->info('     - Control TOTAL de la organización');
        $this->command->info('     - Gestiona billing (Stripe)');
        $this->command->info('     - Invita/remueve usuarios');
        $this->command->info('     - Gestiona todos los recursos compartidos');

        // === 2. ADMIN (ADMINISTRADOR) 🛡️ ===
        $this->command->info('  🛡️  Creando rol: Admin (Administrador)');

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([
            // Organización (sin billing ni eliminar)
            'organization.view',
            'organization.update',
            'organization.view_members',
            'organization.invite_members',
            'organization.remove_members',
            'organization.change_roles',

            // Recursos compartidos (gestión completa)
            'shared.folders.view',
            'shared.folders.create',
            'shared.folders.update',
            'shared.folders.delete',
            'shared.templates.view',
            'shared.templates.create',
            'shared.templates.update',
            'shared.templates.delete',
            'shared.contacts.view',
            'shared.contacts.create',
            'shared.contacts.update',
            'shared.contacts.delete',
        ]);

        $this->command->info("     ✅ {$adminRole->permissions->count()} permisos asignados");
        $this->command->info('     - Co-administrador de la organización');
        $this->command->info('     - Invita usuarios');
        $this->command->info('     - Gestiona recursos compartidos');
        $this->command->info('     - NO puede cambiar billing ni eliminar organización');

        // === 3. MEMBER (MIEMBRO) 👤 ===
        $this->command->info('  👤 Creando rol: Member (Miembro)');

        $memberRole = Role::create(['name' => 'member', 'guard_name' => 'web']);
        $memberRole->givePermissionTo([
            // Organización (solo ver)
            'organization.view',
            'organization.view_members',

            // Recursos compartidos (solo ver y usar)
            'shared.folders.view',
            'shared.templates.view',
            'shared.contacts.view',
        ]);

        $this->command->info("     ✅ {$memberRole->permissions->count()} permisos asignados");
        $this->command->info('     - Usuario normal del equipo');
        $this->command->info('     - Acceso a recursos compartidos (lectura)');
        $this->command->info('     - Gestiona SUS propios emails/carpetas/templates');
        $this->command->info('     - No puede invitar ni administrar');

        // ==================== RESUMEN ====================
        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ SISTEMA DE ROLES Y PERMISOS CREADO EXITOSAMENTE');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();

        $this->command->table(
            ['Rol', 'Permisos', 'Descripción'],
            [
                ['👑 owner', $ownerRole->permissions->count(), 'Control total (billing, eliminar org, todo)'],
                ['🛡️  admin', $adminRole->permissions->count(), 'Co-admin (gestiona recursos, no billing)'],
                ['👤 member', $memberRole->permissions->count(), 'Usuario normal (usa recursos compartidos)'],
            ]
        );

        $this->command->newLine();
        $this->command->info('📝 Notas importantes:');
        $this->command->info('   • Cada usuario SIEMPRE gestiona SUS propios recursos (implícito)');
        $this->command->info('   • NO se comparten emails individuales (cada usuario independiente)');
        $this->command->info('   • Solo se comparten: carpetas, templates y contactos de organización');
        $this->command->info('   • Usa: User::assignRole("owner") para asignar roles');
        $this->command->newLine();
    }

    /**
     * Contar permisos
     */
    private function countPermissions(array $permissions): int
    {
        return count($permissions);
    }
}
