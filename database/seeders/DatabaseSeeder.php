<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\AvailabilityRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Create sample services
        $services = [
            [
                'name' => "Women's Haircut",
                'description' => 'Includes consultation, wash, cut, and style',
                'duration_minutes' => 60,
                'price_cents' => 7500,
                'sort_order' => 1,
            ],
            [
                'name' => "Men's Haircut",
                'description' => 'Classic cut with clipper and scissors',
                'duration_minutes' => 30,
                'price_cents' => 3500,
                'sort_order' => 2,
            ],
            [
                'name' => 'Color - Full',
                'description' => 'Full head color application',
                'duration_minutes' => 120,
                'price_cents' => 15000,
                'sort_order' => 3,
            ],
            [
                'name' => 'Highlights - Partial',
                'description' => 'Face-framing highlights',
                'duration_minutes' => 90,
                'price_cents' => 12000,
                'sort_order' => 4,
            ],
            [
                'name' => 'Blowout',
                'description' => 'Wash and professional blowdry',
                'duration_minutes' => 45,
                'price_cents' => 5000,
                'sort_order' => 5,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        // Create availability rules (business hours)
        $hours = [
            ['day_of_week' => 0, 'is_available' => false], // Sunday - closed
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '18:00', 'is_available' => true],
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '18:00', 'is_available' => true],
            ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '18:00', 'is_available' => true],
            ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '20:00', 'is_available' => true], // Late Thursday
            ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '18:00', 'is_available' => true],
            ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '16:00', 'is_available' => true], // Short Saturday
        ];

        foreach ($hours as $hour) {
            AvailabilityRule::create($hour);
        }
    }
}
