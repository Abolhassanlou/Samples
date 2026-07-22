<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'key' => Feature::KEY_RECURRING_AVAILABILITY,
                'name' => 'Recurring availability',
                'description' => 'Employees can define recurring weekly availability.',
                'category' => 'availability',
                'default_enabled' => false,
                'is_active' => true,
            ],
            [
                'key' => Feature::KEY_DATE_SPECIFIC_AVAILABILITY,
                'name' => 'Date-specific availability',
                'description' => 'Employees can define availability overrides for specific dates.',
                'category' => 'availability',
                'default_enabled' => false,
                'is_active' => true,
            ],
        ];

        foreach ($features as $feature) {
            Feature::query()->updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }
    }
}
