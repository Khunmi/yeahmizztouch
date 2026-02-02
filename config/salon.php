<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Salon Information
    |--------------------------------------------------------------------------
    */
    'name' => env('SALON_NAME', 'Your Salon'),
    'timezone' => env('SALON_TIMEZONE', 'America/New_York'),
    'admin_email' => env('SALON_ADMIN_EMAIL'),
    
    /*
    |--------------------------------------------------------------------------
    | Booking Rules
    |--------------------------------------------------------------------------
    */
    
    // Minutes between appointments (buffer time)
    'buffer_minutes' => env('BUFFER_MINUTES', 15),
    
    // Time slot interval for generating available times
    'slot_interval_minutes' => env('SLOT_INTERVAL_MINUTES', 15),
    
    // Minimum hours in advance for booking
    'min_advance_hours' => env('MIN_ADVANCE_HOURS', 2),
    
    // Maximum days in advance for booking
    'max_advance_days' => env('MAX_ADVANCE_DAYS', 60),
    
    // How long a slot hold lasts (minutes)
    'slot_hold_minutes' => env('SLOT_HOLD_MINUTES', 15),
    
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */
    
    // Require full payment (vs deposit)
    'require_full_payment' => env('REQUIRE_FULL_PAYMENT', false),
    
    // Default deposit percentage (if not requiring full payment)
    'deposit_percentage' => env('DEPOSIT_PERCENTAGE', 25),
    
    /*
    |--------------------------------------------------------------------------
    | Cancellation Policy
    |--------------------------------------------------------------------------
    */
    
    // Hours before appointment that cancellation is allowed
    'cancellation_hours' => env('CANCELLATION_HOURS', 24),
];
