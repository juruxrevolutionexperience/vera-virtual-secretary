<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'        => 'free_trial',
                'name'        => 'Free Trial',
                'email_limit' => 30,
                'token_limit' => 50000,
                'price_eur'   => 0,
                'features'    => ['basic_analysis'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'basic',
                'name'        => 'Basic',
                'email_limit' => 100,
                'token_limit' => 50000,
                'price_eur'   => 5,
                'features'    => ['basic_analysis', 'sentiment'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'starter',
                'name'        => 'Starter',
                'email_limit' => 500,
                'token_limit' => 200000,
                'price_eur'   => 15,
                'features'    => ['basic_analysis', 'sentiment', 'weekly_learning', 'suggested_replies'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'pro',
                'name'        => 'Pro',
                'email_limit' => 2000,
                'token_limit' => 1000000,
                'price_eur'   => 30,
                'features'    => ['basic_analysis', 'sentiment', 'weekly_learning', 'suggested_replies', 'auto_response', 'analytics'],
                'is_active'   => true,
            ],
            [
                'slug'        => 'enterprise',
                'name'        => 'Enterprise',
                'email_limit' => null,
                'token_limit' => null,
                'price_eur'   => 99,
                'features'    => ['basic_analysis', 'sentiment', 'weekly_learning', 'suggested_replies', 'auto_response', 'analytics', 'organizations', 'priority_support'],
                'is_active'   => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
