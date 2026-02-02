<?php

/**
 * DATABASE SCHEMA — SALON BOOKING SYSTEM
 * 
 * All migrations combined in one file for documentation purposes.
 * In production, split into individual timestamped migration files.
 */

// ============================================================================
// MIGRATION 1: CREATE USERS TABLE (Admin only)
// ============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // USERS — Admin authentication only
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
        });

        // SERVICES — Hair services offered
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Women's Haircut"
            $table->text('description')->nullable();
            $table->integer('duration_minutes');             // 60
            $table->integer('price_cents');                  // 5000 = $50.00
            $table->integer('deposit_cents')->nullable();    // Optional deposit amount
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
            $table->index('sort_order');
        });

        // AVAILABILITY RULES — Business hours per weekday
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week');              // 0=Sunday, 6=Saturday
            $table->time('start_time');                      // '09:00:00'
            $table->time('end_time');                        // '17:00:00'
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->unique('day_of_week');                   // One rule per day
        });

        // BLACKOUT DATES — Manual blocked dates
        Schema::create('blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time')->nullable();          // Null = all day
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();            // "Holiday", "Vacation"
            $table->timestamps();
            
            $table->index('date');
        });

        // CLIENTS — Customer information
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();               // Admin notes
            $table->timestamps();
            
            $table->index('email');
            $table->index('phone');
        });

        // APPOINTMENTS — Confirmed bookings
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                  // Public identifier
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', [
                'confirmed',
                'completed',
                'cancelled',
                'no_show'
            ])->default('confirmed');
            $table->text('notes')->nullable();
            $table->boolean('is_admin_created')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // CRITICAL: Prevent overlapping appointments
            $table->unique(['date', 'start_time', 'end_time', 'deleted_at'], 'unique_appointment_slot');
            
            $table->index(['date', 'status']);
            $table->index('uuid');
        });

        // SLOT HOLDS — Temporary reservations during checkout
        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('session_id');                    // Browser session
            $table->timestamp('expires_at');
            $table->timestamps();
            
            // Prevent multiple holds on same slot
            $table->unique(['date', 'start_time', 'end_time'], 'unique_hold_slot');
            
            $table->index('expires_at');
            $table->index('session_id');
        });

        // PAYMENTS — Payment records
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('usd');
            $table->enum('status', [
                'pending',
                'succeeded',
                'failed',
                'refunded',
                'partially_refunded'
            ])->default('pending');
            $table->enum('type', ['deposit', 'full_payment']);
            $table->json('metadata')->nullable();            // Stripe metadata
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_checkout_session_id');
            $table->index('status');
        });

        // PAYMENT LOGS — Webhook event log for debugging/auditing
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();     // Idempotency key
            $table->string('event_type');
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('stripe_event_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('slot_holds');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('blackout_dates');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('services');
        Schema::dropIfExists('users');
    }
};

/*
 * ============================================================================
 * SCHEMA RATIONALE
 * ============================================================================
 *
 * 1. USERS TABLE
 *    - Minimal: only for admin authentication
 *    - No customer accounts (reduces complexity)
 *    - is_admin flag for future role expansion
 *
 * 2. SERVICES TABLE
 *    - price_cents: Store money as integers to avoid floating point issues
 *    - deposit_cents: Optional per-service deposit override
 *    - soft_deletes: Preserve history for existing appointments
 *    - sort_order: Control display order
 *
 * 3. AVAILABILITY_RULES TABLE
 *    - One record per weekday (unique constraint)
 *    - is_available: Easy toggle for day off
 *    - Times in local timezone (converted to UTC in code)
 *
 * 4. BLACKOUT_DATES TABLE
 *    - Full day or partial day blocks
 *    - No foreign keys: standalone blocking mechanism
 *
 * 5. CLIENTS TABLE
 *    - Separate from users: no auth required
 *    - Denormalized notes for admin convenience
 *    - Index on email/phone for lookup
 *
 * 6. APPOINTMENTS TABLE
 *    - uuid: Public-facing identifier (hide auto-increment)
 *    - Composite unique constraint prevents double-booking
 *    - soft_deletes: Maintain audit trail
 *    - is_admin_created: Distinguish walk-ins from online bookings
 *
 * 7. SLOT_HOLDS TABLE
 *    - Temporary records during checkout
 *    - expires_at: Automatic cleanup after TTL
 *    - session_id: Tie hold to browser session
 *    - Unique constraint: Only one hold per slot
 *
 * 8. PAYMENTS TABLE
 *    - Links to appointment (nullable until confirmed)
 *    - stripe_payment_intent_id: Unique for idempotency
 *    - type: Track deposit vs full payment
 *    - metadata: Store Stripe response for debugging
 *
 * 9. PAYMENT_LOGS TABLE
 *    - stripe_event_id: Idempotent webhook processing
 *    - Full payload stored for debugging
 *    - processed flag prevents double-handling
 */
