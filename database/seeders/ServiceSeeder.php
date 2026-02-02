<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Haircut',
                'description' => 'Professional haircut and styling',
                'duration_minutes' => 45,
                'price_cents' => 5000, // $50
                'sort_order' => 1,
            ],
            [
                'name' => 'Color',
                'description' => 'Full color treatment',
                'duration_minutes' => 120,
                'price_cents' => 15000, // $150
                'sort_order' => 2,
            ],
            [
                'name' => 'Highlights',
                'description' => 'Partial or full highlights',
                'duration_minutes' => 90,
                'price_cents' => 12000, // $120
                'sort_order' => 3,
            ],
            [
                'name' => 'Blowout',
                'description' => 'Wash and blowdry styling',
                'duration_minutes' => 30,
                'price_cents' => 3500, // $35
                'sort_order' => 4,
            ],
            [
                'name' => 'Deep Conditioning Treatment',
                'description' => 'Intensive hair treatment',
                'duration_minutes' => 30,
                'price_cents' => 4000, // $40
                'sort_order' => 5,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
