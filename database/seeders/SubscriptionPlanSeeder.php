<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plansConfig = config('subscriptions.plans', []);

        foreach ($plansConfig as $key => $planData) {
            SubscriptionPlan::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $planData['name'],
                    'target_role' => $planData['target_role'],
                    'price' => $planData['price'],
                    'interval_days' => $planData['interval_days'] ?? 30,
                    'order_limit' => $planData['order_limit'] ?? null,
                    'description' => $planData['description'] ?? '',
                    'features' => $planData['features'] ?? [],
                    'is_active' => true,
                ]
            );
        }
    }
}
