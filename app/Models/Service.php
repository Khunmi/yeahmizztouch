<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price_cents',
        'deposit_cents',
        'is_active',
        'sort_order',
        'minimum_age',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price_cents' => 'integer',
        'deposit_cents' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'minimum_age' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            // Auto-calculate deposit as 40% of price
            $depositPercentage = PolicySetting::getValue('deposit_percentage', 40);
            $service->deposit_cents = (int) round($service->price_cents * ($depositPercentage / 100));
        });
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helpers for dollar amounts
    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    public function getDepositAttribute(): float
    {
        return $this->deposit_cents / 100;
    }
}
