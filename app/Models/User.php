<?php

namespace App\Models;

use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }


    public function requestedSeries(): HasMany {
        return $this->hasMany(Series::class, 'requested_by');
    }

    public function acceptedSeries(): HasMany {
        return $this->hasMany(Series::class, 'accepted_by');
    }

    public function requestedClippers(): HasMany {
        return $this->hasMany(Clipper::class, 'requested_by');
    }

    public function acceptedClippers(): HasMany {
        return $this->hasMany(Clipper::class, 'accepted_by');
    }

    /**
     * Get the user's personal clipper collection.
     */
    public function myCollection(): HasMany
    {
        return $this->hasMany(CollectedClipper::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'followed_id')
            ->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'followed_id', 'follower_id')
            ->withTimestamps();
    }

    public function sendEmailVerificationNotification(): void
    {
        RateLimiter::hit($this->emailVerificationRateLimitKey(), 120);

        $this->notify(new VerifyEmailNotification());
    }

    public function emailVerificationRateLimitKey(): string
    {
        return 'email-verification:'.$this->getAuthIdentifier();
    }

    public function emailVerificationCooldownSeconds(): int
    {
        return RateLimiter::availableIn($this->emailVerificationRateLimitKey());
    }
}
