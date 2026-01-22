<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Important for your UUIDs
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Series extends Model
{
    use HasFactory, HasUuids; // Tells Laravel to auto-generate UUIDs for new series

    protected $fillable = ['name', 'custom', 'requested_by', 'accepted_by', 'image_data'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom' => 'boolean',
        ];
    }

    // Relationship: A Series has 4 (usually) Clippers
    public function clippers(): HasMany
    {
        return $this->hasMany(Clipper::class);
    }

    public function acceptedClippers(): HasMany
    {
        return $this->hasMany(Clipper::class)->whereNotNull('accepted_by');
    }

    // Relationship: A Series was requested by a User
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withDefault([
        'name' => 'Deleted User',
    ]);
    }
    
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by')->withDefault([
        'name' => 'Deleted User',
    ]);
    }
    protected function imageData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::url($value) : null,
            set: fn ($value) => $value
        );
    }

    /**
     * Scope a query to only include accepted series.
     */
    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_by');
    }

    /**
     * Scope a query to only include pending series requests.
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_by');
    }
}
