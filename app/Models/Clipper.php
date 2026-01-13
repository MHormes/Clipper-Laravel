<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clipper extends Model
{
    use HasUuids;

    protected $fillable = ['series_id', 'series_number', 'created_by', 'image_data'];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    // This links the "template" to all the times it has been collected
    public function collections(): HasMany
    {
        return $this->hasMany(CollectedClipper::class);
    }

    public function getImageDataAttribute($value)
    {
        if (is_resource($value)) {
            return stream_get_contents($value);
        }
        return $value;
    }
}