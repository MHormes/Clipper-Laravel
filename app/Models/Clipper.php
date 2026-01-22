<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clipper extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['series_id', 'series_number', 'requested_by' ,'accepted_by', 'image_data'];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    // This links the "template" to all the times it has been collected
    public function collections(): HasMany
    {
        return $this->hasMany(CollectedClipper::class);
    }

    protected function imageData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::url($value) : null,
        );
    }

    // Relationship: A Clipper was requested by a User
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withDefault([
            'name' => 'Deleted User',
        ]);
    }

    // Relationship: A Clipper was accepted by an Admin
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by')->withDefault([
            'name' => 'Deleted User',
        ]);
    }

    /**
     * Scope a query to only include accepted clippers.
     */
    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_by');
    }

    /**
     * Scope a query to only include pending clipper requests.
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_by');
    }
}