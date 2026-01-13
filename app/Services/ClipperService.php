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
            

            // 1. Delete explicitly requested clippers
            if (isset($data['deleted_ids']) && is_array($data['deleted_ids'])) {
                foreach ($data['deleted_ids'] as $id) {
                    /** @var \App\Models\Clipper $clipper */
                    $clipper = \App\Models\Clipper::find($id);
                    if ($clipper) {
                        // Delete related collections first to avoid FK constraint issues
                        $clipper->collections()->delete();
                        Storage::disk('public')->delete($clipper->image_data);
                        $clipper->delete();
                    }
                }
            }

            // 2. Main Image: Only replace if a new file is provided
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($series->image_data) {
                    Storage::disk('public')->delete($series->image_data);
                }
                $series->image_data = $data['image']->store('series', 'public');
            }
            
            $series->save();

            // 3. Clipper Slots: Update or Create based on form index
            if (isset($data['clippers']) && is_array($data['clippers'])) {
                foreach ($data['clippers'] as $index => $clipperData) {
                    $slotNumber = $index + 1;

                    if (isset($clipperData['image']) && $clipperData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        /** @var \App\Models\Clipper $clipper */
                        $clipper = $series->clippers()->where('series_number', $slotNumber)->first();

                        if ($clipper) {
                            Storage::disk('public')->delete($clipper->image_data);
                            $clipper->update([
                                'image_data' => $clipperData['image']->store('clippers', 'public')
                            ]);
                        } else {
                            $series->clippers()->create([
                                'series_number' => $slotNumber,
                                'image_data'    => $clipperData['image']->store('clippers', 'public'),
                                'created_by'    => $user->id,
                            ]);
                        }
                    }
                }
            }

            // 4. Re-index if series is custom to ensure sequential numbering (1, 2, 3...)
            if ($series->custom) {
                $series->refresh();
                $series->clippers()->orderBy('series_number')->get()->each(function ($clipper, $index) {
                    /** @var \App\Models\Clipper $clipper */
                    $clipper->update(['series_number' => $index + 1]);
                });
            }
        });
    }

    public function deleteSeries(Series $series)
    {
        return DB::transaction(function () use ($series) {
            // Delete all clipper images
            foreach ($series->clippers as $clipper) {
                // Delete collections for each clipper in this series
                $clipper->collections()->delete();
                Storage::disk('public')->delete($clipper->image_data);
            }
            // Delete main series image
            Storage::disk('public')->delete($series->image_data);
            // Delete database record
            return $series->delete();
        });
    }

    public function getSeriesCatalog(?int $limit = null, ?string $search = null)
    {
        $query = Series::withCount('clippers')
            ->with(['creator:id,name'])
            ->latest();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($limit) {
            return $query->limit($limit)->get();
        }

        return $query->paginate(12)->withQueryString();
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