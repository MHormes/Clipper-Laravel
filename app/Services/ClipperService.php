<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Clipper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ClipperService
{
    /**
     * Create a brand new series and its clippers.
     */
    public function createSeriesWithClippers($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Store Main Series Image
            $seriesPath = $data['image']->store('series', 'public');

            $series = Series::create([
                'name'       => $data['name'],
                'custom'  => filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN),
                'image_data' => $seriesPath,
                'created_by' => $user->id,
            ]);

            // 2. Loop through the 4 possible clipper slots in the form
            foreach ($data['clippers'] as $index => $clipperData) {
                if (isset($clipperData['image']) && $clipperData['image'] instanceof UploadedFile) {
                    $path = $clipperData['image']->store('clippers', 'public');

                    $series->clippers()->create([
                        'series_number' => $index + 1,
                        'image_data'    => $path,
                        'created_by'    => $user->id,
                    ]);
                }
            }

            return $series;
        });
    }

    /**
     * Update an existing series and manage clipper slots.
     */
    public function updateSeries(Series $series, $user, array $data)
    {
        return DB::transaction(function () use ($series, $user, $data) {
            // 1. Update Basic Fields
            $series->name = $data['name'];
            $series->custom = filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN);
            

            // 2. Main Image: Only replace if a new file is provided
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($series->image_data) {
                    Storage::disk('public')->delete($series->image_data);
                }
                $series->image_data = $data['image']->store('series', 'public');
            }
            
            $series->save();

            // 3. Clipper Slots (Looping 0-3 based on form structure)
            if (isset($data['clippers']) && is_array($data['clippers'])) {
                foreach ($data['clippers'] as $index => $clipperData) {
                    $slotNumber = $index + 1;

                    // We only act if a NEW image file was uploaded for this slot
                    if (isset($clipperData['image']) && $clipperData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        
                        // Look for an existing clipper in this specific slot
                        $clipper = $series->clippers()->where('series_number', $slotNumber)->first();

                        if ($clipper) {
                            // Cleanup old file and update record
                            Storage::disk('public')->delete($clipper->image_data);
                            $clipper->update([
                                'image_data' => $clipperData['image']->store('clippers', 'public')
                            ]);
                        } else {
                            // Slot was previously empty; create new record
                            $series->clippers()->create([
                                'series_number' => $slotNumber,
                                'image_data'    => $clipperData['image']->store('clippers', 'public'),
                                'created_by'    => $user->id,
                            ]);
                        }
                    }
                }
            }
        });
    }

    public function deleteSeries(Series $series)
    {
        return DB::transaction(function () use ($series) {
            // Delete all clipper images
            foreach ($series->clippers as $clipper) {
                Storage::disk('public')->delete($clipper->image_data);
            }
            // Delete main series image
            Storage::disk('public')->delete($series->image_data);
            // Delete database record
            return $series->delete();
        });
    }

        public function getSeriesCatalog(?int $limit = null)
    {
        $query = Series::withCount('clippers')
            ->with('creator:id,name') // Eager load the creator for the "Added by" text
            ->latest();

        if ($limit) {
            return $query->limit($limit)->get();
        }

        return $query->paginate(12);
    }

    public function getCollectedClippersForSeries(Series $series, ?User $user)
    {
        if (!$user) {
            return collect();
        }

        return Clipper::whereIn('id', function ($query) use ($user, $series) {
            $query->select('clipper_id')
                ->from('collected_clippers')
                ->where('user_id', $user->id)
                ->whereIn('clipper_id', function ($subQuery) use ($series) {
                    $subQuery->select('id')
                        ->from('clippers')
                        ->where('series_id', $series->id);
                });
        })->pluck('series_number')->toArray();
    }
}