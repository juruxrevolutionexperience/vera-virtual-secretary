<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class UserPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Actualizar usuarios existentes con plan básico por defecto
        User::whereNull('plan_type')->update([
            'plan_type' => 'basic',
            'monthly_email_limit' => 100,
            'billing_cycle_start' => Carbon::now()->startOfMonth(),
            'plan_metadata' => json_encode([
                'initial_plan' => 'basic',
                'assigned_at' => Carbon::now()->toDateTimeString(),
                'source' => 'migration_seeder',
            ]),
        ]);

        $this->command->info('✅ Usuarios existentes configurados con plan Basic (100 emails/mes)');

        // Mostrar resumen
        $basicCount = User::where('plan_type', 'basic')->count();
        $starterCount = User::where('plan_type', 'starter')->count();
        $proCount = User::where('plan_type', 'pro')->count();

        $this->command->info('');
        $this->command->info('📊 Resumen de planes:');
        $this->command->info("   Basic: {$basicCount} usuarios");
        $this->command->info("   Starter: {$starterCount} usuarios");
        $this->command->info("   Pro: {$proCount} usuarios");
        $this->command->info('');
    }
}
