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

    protected $fillable = ['series_id', 'series_number', 'accepted_by' ,'requested_by', 'image_data'];

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
}