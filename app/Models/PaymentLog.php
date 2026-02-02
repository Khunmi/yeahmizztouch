<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'payload',
        'processed',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('error_message');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if this event has already been processed (idempotency).
     */
    public static function alreadyProcessed(string $eventId): bool
    {
        return static::where('stripe_event_id', $eventId)
                    ->where('processed', true)
                    ->exists();
    }

    /**
     * Log a Stripe webhook event.
     */
    public static function logEvent(string $eventId, string $eventType, array $payload): self
    {
        return static::create([
            'stripe_event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'processed' => false,
        ]);
    }

    public function markProcessed(): bool
    {
        $this->processed = true;
        return $this->save();
    }

    public function markFailed(string $errorMessage): bool
    {
        $this->error_message = $errorMessage;
        return $this->save();
    }
}
