<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SlotHold extends Model
{
    protected $fillable = [
        'uuid',
        'service_id',
        'date',
        'start_time',
        'end_time',
        'session_id',
        'expires_at',
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // BOOT
    // ─────────────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($hold) {
            if (empty($hold->uuid)) {
                $hold->uuid = Str::uuid()->toString();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function getRemainingMinutesAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInMinutes($this->expires_at);
    }

    public function getFormattedTimeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');
        return "{$start} - {$end}";
    }

    /**
     * Extend hold expiration.
     */
    public function extend(int $minutes = null): bool
    {
        $minutes = $minutes ?? config('salon.slot_hold_minutes', 15);
        $this->expires_at = now()->addMinutes($minutes);
        
        return $this->save();
    }

    /**
     * Check if this hold overlaps with given time range.
     */
    public function overlaps(string $date, string $startTime, string $endTime): bool
    {
        if ($this->date->format('Y-m-d') !== $date) {
            return false;
        }

        $thisStart = Carbon::parse($this->start_time);
        $thisEnd = Carbon::parse($this->end_time);
        $checkStart = Carbon::parse($startTime);
        $checkEnd = Carbon::parse($endTime);

        return $checkStart->lt($thisEnd) && $checkEnd->gt($thisStart);
    }

    /**
     * Delete all expired holds.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}
