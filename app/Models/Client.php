<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhotoConsent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'photo_consent',
        'policy_acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'photo_consent' => PhotoConsent::class,
        'policy_acknowledged_at' => 'datetime',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function meetsMinimumAge(int $minimumAge = 15): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }
        return $this->age >= $minimumAge;
    }
}
