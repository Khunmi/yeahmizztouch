<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PhotoConsent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'service_id',
        'starts_at',
        'ends_at',
        'status',
        'hold_expires_at',
        'service_price_cents',
        'deposit_amount_cents',
        'late_fee_cents',
        'squeeze_in_fee_cents',
        'cancellation_reason',
        'cancelled_at',
        'cancellation_charge_cents',
        'photo_consent_at_booking',
        'policy_acknowledged_at',
        'is_squeeze_in',
        'admin_notes',
    ];

    protected $casts = [
        'starts_at' => 'immutable_datetime',
        'ends_at' => 'immutable_datetime',
        'hold_expires_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
        'policy_acknowledged_at' => 'immutable_datetime',
        'status' => AppointmentStatus::class,
        'photo_consent_at_booking' => PhotoConsent::class,
        'service_price_cents' => 'integer',
        'deposit_amount_cents' => 'integer',
        'late_fee_cents' => 'integer',
        'squeeze_in_fee_cents' => 'integer',
        'cancellation_charge_cents' => 'integer',
        'is_squeeze_in' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            AppointmentStatus::Held,
            AppointmentStatus::Confirmed,
        ]);
    }

    public function scopeHeld($query)
    {
        return $query->where('status', AppointmentStatus::Held);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', AppointmentStatus::Confirmed);
    }

    public function scopeExpiredHolds($query)
    {
        return $query
            ->where('status', AppointmentStatus::Held)
            ->where('hold_expires_at', '<', CarbonImmutable::now());
    }

    public function scopeBlocksSlot($query)
    {
        return $query
            ->whereIn('status', [AppointmentStatus::Held, AppointmentStatus::Confirmed])
            ->where(function ($q) {
                $q->whereNull('hold_expires_at')
                    ->orWhere('hold_expires_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeOverlapping($query, CarbonImmutable $startsAt, CarbonImmutable $endsAt)
    {
        return $query
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
    }

    // Helpers
    public function isHoldExpired(): bool
    {
        if ($this->status !== AppointmentStatus::Held) {
            return false;
        }
        return $this->hold_expires_at && $this->hold_expires_at->isPast();
    }

    public function canBeCancelledByClient(): bool
    {
        if (!$this->status->isActive()) {
            return false;
        }
        
        $cutoffHours = PolicySetting::getValue('reschedule_cutoff_hours', 48);
        $cutoff = $this->starts_at->subHours($cutoffHours);
        
        return CarbonImmutable::now()->isBefore($cutoff);
    }

    public function isWithinLateCancelWindow(): bool
    {
        $cutoffHours = PolicySetting::getValue('reschedule_cutoff_hours', 48);
        $cutoff = $this->starts_at->subHours($cutoffHours);
        
        return CarbonImmutable::now()->isAfter($cutoff) 
            && CarbonImmutable::now()->isBefore($this->starts_at);
    }

    public function getTotalChargedCentsAttribute(): int
    {
        return $this->payments()
            ->where('status', 'succeeded')
            ->sum('amount_cents');
    }
}
