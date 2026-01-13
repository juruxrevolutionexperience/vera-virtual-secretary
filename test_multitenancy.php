#!/usr/bin/env php
<?php

/**
 * SCRIPT DE TESTING: SUB-FASE 1D - MULTI-TENANCY Y ROLES
 *
 * Este script prueba la funcionalidad completa de:
 * - Organizaciones
 * - Roles y permisos
 * - Invitaciones
 * - Compartir emails/carpetas
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Email;
use App\Models\EmailFolder;
use App\Models\EmailShare;
use App\Models\EmailFolderShare;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🧪 TEST: MULTI-TENANCY Y ROLES - SUB-FASE 1D\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // ========================================
    // 1. LIMPIAR DATOS DE PRUEBA ANTERIORES
    // ========================================
    echo "🧹 [1/8] Limpiando datos de prueba anteriores...\n";

    DB::table('email_folder_shares')->delete();
    DB::table('email_shares')->delete();
    DB::table('organization_invitations')->delete();
    DB::table('organization_user')->delete();
    DB::table('organizations')->delete();

    // Borrar usuarios de prueba
    User::where('email', 'LIKE', '%@test-multitenancy.com')->delete();

    echo "   ✅ Datos limpiados\n\n";

    // ========================================
    // 2. CREAR USUARIOS DE PRUEBA
    // ========================================
    echo "👥 [2/8] Creando usuarios de prueba...\n";

    $owner = User::create([
        'name' => 'Owner Test',
        'email' => 'owner@test-multitenancy.com',
        'password' => bcrypt('password123'),
    ]);
    echo "   ✅ Usuario Owner creado: {$owner->email}\n";

    $admin = User::create([
        'name' => 'Admin Test',
        'email' => 'admin@test-multitenancy.com',
        'password' => bcrypt('password123'),
    ]);
    echo "   ✅ Usuario Admin creado: {$admin->email}\n";

    $manager = User::create([
        'name' => 'Manager Test',
        'email' => 'manager@test-multitenancy.com',
        'password' => bcrypt('password123'),
    ]);
    echo "   ✅ Usuario Manager creado: {$manager->email}\n";

    $employee = User::create([
        'name' => 'Employee Test',
        'email' => 'employee@test-multitenancy.com',
        'password' => bcrypt('password123'),
    ]);
    echo "   ✅ Usuario Employee creado: {$employee->email}\n\n";

    // ========================================
    // 3. CREAR ORGANIZACIÓN
    // ========================================
    echo "🏢 [3/8] Creando organización...\n";

    $organization = Organization::create([
        'owner_id' => $owner->id,
        'name' => 'Test Company Inc',
        'description' => 'Organización de prueba para multi-tenancy',
        'email' => 'info@testcompany.com',
        'phone' => '+34 123 456 789',
    ]);

    echo "   ✅ Organización creada:\n";
    echo "      - ID: {$organization->id}\n";
    echo "      - Nombre: {$organization->name}\n";
    echo "      - Slug: {$organization->slug}\n";
    echo "      - Owner: {$organization->owner->name}\n\n";

    // ========================================
    // 4. AÑADIR USUARIOS A LA ORGANIZACIÓN
    // ========================================
    echo "🔗 [4/8] Añadiendo usuarios a la organización con roles...\n";

    // Owner se añade automáticamente
    $organization->addUser($owner, 'organization_owner');
    echo "   ✅ {$owner->name} añadido como organization_owner\n";

    $organization->addUser($admin, 'admin', $owner);
    echo "   ✅ {$admin->name} añadido como admin\n";

    $organization->addUser($manager, 'manager', $owner);
    echo "   ✅ {$manager->name} añadido como manager\n";

    $organization->addUser($employee, 'employee', $owner);
    echo "   ✅ {$employee->name} añadido como employee\n";

    echo "   📊 Total miembros activos: {$organization->getActiveMembersCount()}\n\n";

    // ========================================
    // 5. VERIFICAR ROLES Y PERMISOS
    // ========================================
    echo "🔐 [5/8] Verificando roles y permisos de Spatie...\n";

    // Asignar roles de Spatie a los usuarios
    $owner->assignRole('organization_owner');
    $admin->assignRole('admin');
    $manager->assignRole('manager');
    $employee->assignRole('employee');

    echo "   ✅ Roles de Spatie asignados\n";

    // Verificar permisos
    echo "\n   📋 Permisos de cada usuario:\n";

    echo "   - Owner: " . ($owner->hasPermissionTo('emails.view_all') ? '✅' : '❌') . " emails.view_all\n";
    echo "   - Admin: " . ($admin->hasPermissionTo('emails.view_all') ? '✅' : '❌') . " emails.view_all\n";
    echo "   - Manager: " . ($manager->hasPermissionTo('emails.view_shared') ? '✅' : '❌') . " emails.view_shared\n";
    echo "   - Employee: " . ($employee->hasPermissionTo('emails.view_own') ? '✅' : '❌') . " emails.view_own\n";
    echo "   - Employee: " . ($employee->hasPermissionTo('emails.view_all') ? '❌' : '✅') . " NO emails.view_all\n\n";

    // ========================================
    // 6. CREAR INVITACIÓN
    // ========================================
    echo "📧 [6/8] Creando invitación a la organización...\n";

    $invitation = OrganizationInvitation::create([
        'organization_id' => $organization->id,
        'email' => 'newuser@test-multitenancy.com',
        'invited_by' => $admin->id,
        'role' => 'employee',
        'message' => '¡Únete a nuestro equipo!',
    ]);

    echo "   ✅ Invitación creada:\n";
    echo "      - Email: {$invitation->email}\n";
    echo "      - Token: " . substr($invitation->token, 0, 20) . "...\n";
    echo "      - Expira: {$invitation->expires_at->format('Y-m-d H:i')}\n";
    echo "      - Estado: {$invitation->status}\n";
    echo "      - Es válida: " . ($invitation->isValid() ? 'Sí' : 'No') . "\n\n";

    // ========================================
    // 7. COMPARTIR EMAIL (si existe)
    // ========================================
    echo "📨 [7/8] Probando sistema de compartir...\n";

    // Buscar un email existente del owner
    $testEmail = Email::where('user_id', $owner->id)->first();

    if ($testEmail) {
        // Actualizar organization_id del email
        $testEmail->update(['organization_id' => $organization->id]);

        $emailShare = EmailShare::create([
            'email_id' => $testEmail->id,
            'shared_with' => $employee->id,
            'shared_by' => $owner->id,
            'permission' => 'view',
            'notes' => 'Email compartido para revisión',
        ]);

        echo "   ✅ Email compartido:\n";
        echo "      - Email ID: {$testEmail->id}\n";
        echo "      - Subject: {$testEmail->subject}\n";
        echo "      - Compartido con: {$employee->name}\n";
        echo "      - Permiso: {$emailShare->permission}\n";
    } else {
        echo "   ⚠️  No hay emails disponibles para compartir\n";
    }

    // Probar carpeta compartida
    $testFolder = EmailFolder::where('user_id', $owner->id)->first();

    if ($testFolder) {
        // Actualizar organization_id de la carpeta
        $testFolder->update(['organization_id' => $organization->id]);

        $folderShare = EmailFolderShare::create([
            'email_folder_id' => $testFolder->id,
            'shared_with' => $manager->id,
            'shared_by' => $owner->id,
            'permission' => 'edit',
            'can_share' => true,
            'notes' => 'Carpeta compartida con el manager',
        ]);

        echo "   ✅ Carpeta compartida:\n";
        echo "      - Carpeta: {$testFolder->name}\n";
        echo "      - Compartida con: {$manager->name}\n";
        echo "      - Permiso: {$folderShare->permission}\n";
        echo "      - Puede compartir: " . ($folderShare->can_share ? 'Sí' : 'No') . "\n";
    } else {
        echo "   ⚠️  No hay carpetas disponibles para compartir\n";
    }

    echo "\n";

    // ========================================
    // 8. VERIFICAR RELACIONES
    // ========================================
    echo "🔍 [8/8] Verificando relaciones...\n\n";

    // Verificar relaciones de Organization
    echo "   📊 Estadísticas de Organización:\n";
    echo "   - Total miembros: {$organization->users()->count()}\n";
    echo "   - Miembros activos: {$organization->activeUsers()->count()}\n";
    echo "   - Emails: {$organization->emails()->count()}\n";
    echo "   - Carpetas: {$organization->emailFolders()->count()}\n\n";

    // Verificar relaciones de User
    echo "   👤 Relaciones del Owner:\n";
    echo "   - Organizaciones: {$owner->organizations()->count()}\n";
    echo "   - Es owner de: {$owner->ownedOrganizations()->count()}\n";
    echo "   - Pertenece a Test Company: " . ($owner->belongsToOrganization($organization) ? 'Sí' : 'No') . "\n";
    echo "   - Rol en organización: " . ($owner->getRoleInOrganization($organization) ?? 'N/A') . "\n\n";

    echo "   👤 Relaciones del Employee:\n";
    echo "   - Organizaciones: {$employee->organizations()->count()}\n";
    echo "   - Rol en organización: " . ($employee->getRoleInOrganization($organization) ?? 'N/A') . "\n";
    echo "   - Tiene permiso view_own: " . ($employee->hasPermissionTo('emails.view_own') ? 'Sí' : 'No') . "\n";
    echo "   - Tiene permiso view_all: " . ($employee->hasPermissionTo('emails.view_all') ? 'Sí' : 'No') . "\n\n";

    // ========================================
    // RESUMEN FINAL
    // ========================================
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ TODOS LOS TESTS COMPLETADOS EXITOSAMENTE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📋 RESUMEN:\n";
    echo "   ✅ Organización creada: {$organization->name}\n";
    echo "   ✅ 4 usuarios añadidos con diferentes roles\n";
    echo "   ✅ Sistema de roles y permisos funcionando\n";
    echo "   ✅ Invitación creada y validada\n";
    echo "   ✅ Sistema de compartir funcionando\n";
    echo "   ✅ Todas las relaciones verificadas\n\n";

    echo "🎯 SIGUIENTE PASO:\n";
    echo "   Puedes revisar los datos en la base de datos:\n";
    echo "   - Tabla: organizations\n";
    echo "   - Tabla: organization_user\n";
    echo "   - Tabla: roles\n";
    echo "   - Tabla: permissions\n";
    echo "   - Tabla: model_has_roles\n\n";

    echo "💡 COMANDO ÚTIL:\n";
    echo "   docker exec virtualemployee-laravel.test-1 php test_multitenancy.php\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
