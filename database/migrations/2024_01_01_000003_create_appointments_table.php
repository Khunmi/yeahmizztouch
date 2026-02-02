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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            
            // Time slot (UTC)
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            
            // Lifecycle
            $table->string('status', 30)->default('held');
            $table->timestampTz('hold_expires_at')->nullable();
            
            // Pricing snapshot at booking time
            $table->integer('service_price_cents');
            $table->integer('deposit_amount_cents');
            $table->integer('late_fee_cents')->default(0);
            $table->integer('squeeze_in_fee_cents')->default(0);
            
            // Cancellation / no-show
            $table->string('cancellation_reason', 50)->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->integer('cancellation_charge_cents')->default(0);
            
            // Consent captured at booking
            $table->string('photo_consent_at_booking', 20);
            $table->timestampTz('policy_acknowledged_at');
            
            // Admin flags
            $table->boolean('is_squeeze_in')->default(false);
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Prevent overlapping confirmed/held appointments
            $table->index(['starts_at', 'ends_at', 'status']);
            $table->index(['client_id', 'starts_at']);
            $table->index(['status', 'hold_expires_at']);
        });

        // PostgreSQL exclusion constraint for bulletproof overlap prevention
        // This requires the btree_gist extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        
        DB::statement("
            ALTER TABLE appointments 
            ADD CONSTRAINT appointments_no_overlap 
            EXCLUDE USING gist (
                tstzrange(starts_at, ends_at) WITH &&
            )
            WHERE (status IN ('held', 'confirmed') AND deleted_at IS NULL)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_no_overlap');
        Schema::dropIfExists('appointments');
    }
};
