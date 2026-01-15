<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectedClipper extends Model
{
    use HasUuids;

    protected $fillable = ['clipper_id', 'user_id', 'notes', 'location_bought'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clipper(): BelongsTo
    {
        return $this->belongsTo(Clipper::class);
    }
}