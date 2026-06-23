<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // ─── Los 3 planes REALES (activos). slug interno → nombre comercial ───
            [
                'slug'        => 'basic',
                'name'        => 'Básico',
                'email_limit' => 500,      // mensajes/mes
                'token_limit' => 600000,
                'price_eur'   => 49,
                'features'    => ['basic_analysis', 'sentiment', 'suggested_replies', 'auto_response'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'pro',
                'name'        => 'Pro',
                'email_limit' => 2000,
                'token_limit' => 2400000,
                'price_eur'   => 89,
                'features'    => ['basic_analysis', 'sentiment', 'weekly_learning', 'suggested_replies', 'auto_response', 'analytics', 'crm_basic'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'enterprise',
                'name'        => 'Centro',
                'email_limit' => 10000,
                'token_limit' => 0,        // 0 = ilimitado
                'price_eur'   => 149,
                'features'    => ['basic_analysis', 'sentiment', 'weekly_learning', 'suggested_replies', 'auto_response', 'analytics', 'organizations', 'priority_support', 'crm_basic', 'crm_advanced'],
                'is_active'   => true,
            ],
            // ─── Planes antiguos → DESACTIVADOS (no se muestran; se mantienen por compatibilidad) ───
            ['slug' => 'beta',       'name' => 'Beta',       'email_limit' => 100, 'token_limit' => 200000, 'price_eur' => 0,  'features' => ['basic_analysis'], 'is_active' => false],
            ['slug' => 'free_trial', 'name' => 'Free Trial', 'email_limit' => 30,  'token_limit' => 50000,  'price_eur' => 0,  'features' => ['basic_analysis'], 'is_active' => false],
            ['slug' => 'starter',    'name' => 'Starter',    'email_limit' => 500, 'token_limit' => 200000, 'price_eur' => 15, 'features' => ['basic_analysis'], 'is_active' => false],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
