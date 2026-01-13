<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Important for your UUIDs
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Series extends Model
{
    use HasUuids; // Tells Laravel to auto-generate UUIDs for new series

    protected $fillable = ['name', 'custom', 'created_by', 'image_data'];

    // Relationship: A Series has 4 (usually) Clippers
    public function clippers(): HasMany
    {
        return $this->hasMany(Clipper::class);
    }

    // Relationship: A Series was created by a User
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
        'name' => 'Deleted User',
    ]);
    }

    protected function imageData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Storage::url($value) : null,
        );
    }
}
