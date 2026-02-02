<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'client_id',
        'stripe_payment_intent_id',
        'stripe_payment_method_id',
        'stripe_customer_id',
        'amount_cents',
        'currency',
        'payment_type',
        'status',
        'stripe_metadata',
        'failure_reason',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'payment_type' => PaymentType::class,
        'status' => PaymentStatus::class,
        'stripe_metadata' => 'array',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', PaymentStatus::Succeeded);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending);
    }
}
