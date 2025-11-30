<?php

namespace Database\Seeders;

use App\Models\EmailCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmailCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Obtenemos el primer usuario para testing

        if (!$user) {
            $this->command->warn('No hay usuarios en la base de datos. Crea uno primero.');
            return;
        }

        $categories = [
            [
                'user_id' => $user->id,
                'name' => 'Inbox',
                'slug' => 'inbox',
                'color' => '#6366f1',
                'icon' => '📥',
                'order' => 1,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Bandeja de entrada principal',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Importante',
                'slug' => 'important',
                'color' => '#ef4444',
                'icon' => '⭐',
                'order' => 2,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Emails marcados como importantes',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Respondidos',
                'slug' => 'replied',
                'color' => '#10b981',
                'icon' => '✓',
                'order' => 3,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Emails que ya fueron respondidos',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Archivados',
                'slug' => 'archived',
                'color' => '#6b7280',
                'icon' => '📦',
                'order' => 4,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Emails archivados',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Proyectos Urgentes',
                'slug' => 'urgent-projects',
                'color' => '#f59e0b',
                'icon' => '🔥',
                'order' => 5,
                'is_system' => false,
                'is_active' => true,
                'description' => 'Emails relacionados con proyectos urgentes',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Clientes VIP',
                'slug' => 'vip-clients',
                'color' => '#8b5cf6',
                'icon' => '👑',
                'order' => 6,
                'is_system' => false,
                'is_active' => true,
                'description' => 'Comunicaciones con clientes prioritarios',
            ],
        ];

        foreach ($categories as $category) {
            EmailCategory::create($category);
        }

        $this->command->info('✓ Categorías de email creadas exitosamente');
    }
}
