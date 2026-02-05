<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BlackoutDate extends Model
{
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                     ->orderBy('date');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if this blackout blocks the entire day.
     */
    public function isFullDay(): bool
    {
        return is_null($this->start_time) && is_null($this->end_time);
    }

    /**
     * Check if a time range overlaps with this blackout.
     */
    public function overlaps(string $startTime, string $endTime): bool
    {
        // Full day block
        if ($this->isFullDay()) {
            return true;
        }

        $blockStart = Carbon::parse($this->start_time);
        $blockEnd = Carbon::parse($this->end_time);
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        // Check for overlap: slot starts before block ends AND slot ends after block starts
        return $slotStart->lt($blockEnd) && $slotEnd->gt($blockStart);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('l, F j, Y');
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        if ($this->isFullDay()) {
            return 'All Day';
        }

        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');
        
        return "{$start} - {$end}";
    }
}
