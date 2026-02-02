<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('value', 255);
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default policy settings
        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $settings = [
            ['key' => 'deposit_percentage', 'value' => '40', 'type' => 'integer', 'description' => 'Deposit percentage of service price'],
            ['key' => 'no_show_charge_percentage', 'value' => '70', 'type' => 'integer', 'description' => 'No-show charge percentage of service price'],
            ['key' => 'late_cancel_charge_percentage', 'value' => '70', 'type' => 'integer', 'description' => 'Late cancellation charge percentage'],
            ['key' => 'reschedule_cutoff_hours', 'value' => '48', 'type' => 'integer', 'description' => 'Hours before appointment for free reschedule'],
            ['key' => 'late_fee_threshold_minutes', 'value' => '20', 'type' => 'integer', 'description' => 'Minutes late before fee applies'],
            ['key' => 'late_fee_cents', 'value' => '2000', 'type' => 'integer', 'description' => 'Late fee amount in cents ($20)'],
            ['key' => 'auto_cancel_minutes_late', 'value' => '40', 'type' => 'integer', 'description' => 'Minutes late before auto-cancel'],
            ['key' => 'squeeze_in_fee_cents', 'value' => '4000', 'type' => 'integer', 'description' => 'Emergency/squeeze-in fee in cents ($40)'],
            ['key' => 'minimum_client_age', 'value' => '15', 'type' => 'integer', 'description' => 'Minimum client age'],
            ['key' => 'hold_duration_minutes', 'value' => '10', 'type' => 'integer', 'description' => 'How long a hold lasts before expiring'],
            ['key' => 'business_hours_start', 'value' => '09:00', 'type' => 'string', 'description' => 'Business hours start (HH:MM)'],
            ['key' => 'business_hours_end', 'value' => '18:00', 'type' => 'string', 'description' => 'Business hours end (HH:MM)'],
            ['key' => 'slot_interval_minutes', 'value' => '15', 'type' => 'integer', 'description' => 'Booking slot interval'],
            ['key' => 'buffer_minutes', 'value' => '0', 'type' => 'integer', 'description' => 'Buffer between appointments'],
        ];

        foreach ($settings as $setting) {
            DB::table('policy_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_settings');
    }
};
