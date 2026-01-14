<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Clipper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClipperService
{
   /**
     * Create a brand new series and its clippers.
     */
    public function createSeriesWithClippers($user, array $data)
    {
        $seriesPath = $this->uploadImage($data['image'], 'series');

        $series = Series::create([
            'name'         => $data['name'],
            'custom'       => filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN),
            'image_data'   => $seriesPath,
            'requested_by' => $user->id,
            'accepted_by'  => $user->id,
        ]);

        foreach ($data['clippers'] as $index => $clipperData) {
            if (isset($clipperData['image']) && $clipperData['image'] instanceof UploadedFile) {
                $path = $this->uploadImage($clipperData['image'], 'clippers');

                $series->clippers()->create([
                    'series_number' => $index + 1,
                    'image_data'    => $path,
                    'requested_by'  => $user->id,
                    'accepted_by'   => $user->id,
                ]);
            }
        }

        return $series;
    }

    /**
     * Update an existing series and manage clipper slots.
     */
    public function updateSeries(Series $series, $user, array $data)
    {
        $series->name = $data['name'];
        $series->custom = filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN);

        // 1. Delete explicitly requested clippers
        if (!empty($data['deleted_ids'])) {
            $clippersToDelete = Clipper::whereIn('id', $data['deleted_ids'])->get();
            foreach ($clippersToDelete as $clipper) {
                $clipper->collections()->delete();
                // Use getRawOriginal to get "clippers/file.jpg" instead of "https://..."
                $this->deleteImage($clipper->getRawOriginal('image_data'));
                $clipper->delete();
            }
        }

        // 2. Main Image Update
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $this->deleteImage($series->getRawOriginal('image_data'));
            $series->image_data = $this->uploadImage($data['image'], 'series');
        }
        
        $series->accepted_by = $user->id;
        $series->save();

        // 3. Clipper Slots Update/Create
        if (isset($data['clippers']) && is_array($data['clippers'])) {
            foreach ($data['clippers'] as $index => $clipperData) {
                $slotNumber = $index + 1;

                if (isset($clipperData['image']) && $clipperData['image'] instanceof UploadedFile) {
                    $clipper = $series->clippers()->where('series_number', $slotNumber)->first();

                    if ($clipper) {
                        $this->deleteImage($clipper->getRawOriginal('image_data'));
                        $clipper->update([
                            'image_data'  => $this->uploadImage($clipperData['image'], 'clippers'),
                            'accepted_by' => $user->id,
                        ]);
                    } else {
                        $series->clippers()->create([
                            'series_number' => $slotNumber,
                            'image_data'    => $this->uploadImage($clipperData['image'], 'clippers'),
                            'requested_by'  => $user->id,
                            'accepted_by'   => $user->id,
                        ]);
                    }
                }
            }
        }

        // 4. Re-index custom series
        if ($series->custom) {
            $series->clippers()->orderBy('series_number')->get()->each(function ($clipper, $index) {
                $clipper->update(['series_number' => $index + 1]);
            });
        }
        
        return $series;
    }

    public function deleteSeries(Series $series)
    {
        foreach ($series->clippers as $clipper) {
            $clipper->collections()->delete();
            $this->deleteImage($clipper->getRawOriginal('image_data'));
            $clipper->delete();
        }

        $this->deleteImage($series->getRawOriginal('image_data'));
        return $series->delete();
    }

    /**
     * Environment dependent upload
     */
    private function uploadImage(UploadedFile $file, string $folder): string
    {
        return $file->store($folder);
    }

    /**
     * Environment dependent delete
     */
    private function deleteImage(?string $path)
    {
        if (!$path) return;

        // If using Cloudinary, it needs the path without the extension sometimes.
        // But Laravel's Storage::delete() usually handles this if the disk is set correctly.
        Storage::delete($path);
    }

    public function getSeriesCatalog(User $user, ?int $limit = null, ?string $search = null, ?string $sortCol ='created_at', ?string $sortDir ='desc')
    {
        $column = filled($sortCol) ? $sortCol : 'created_at';
        $direction = in_array(strtolower($sortDir ?? ''), ['asc', 'desc']) ? $sortDir : 'desc';

        $query = Series::withCount('clippers')
            ->with(['requester:id,name'])
            ->withCount(['clippers as collected_clippers_count' => function ($query) use ($user) {
            $query->whereHas('collections', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }])
            ->orderBy($column, $direction);

            if ($search) {
            $query->where('name', 'ilike', '%' . $search . '%');
        }

        if ($limit) {
            return $query->limit($limit)->get();
        }

        return $query->paginate(15)->withQueryString();
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

    public function countCompletedSeries(User $user): int
    {
        // 1. Fetch series with:
        //    - clippers_count: Total clippers in the series
        //    - collected_count: How many distinct clippers of this series the user owns
        $seriesStats = Series::withCount(['clippers', 'clippers as collected_count' => function ($query) use ($user) {
            $query->whereHas('collections', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }])->get();

        // 2. Filter in memory
        return $seriesStats->filter(function ($series) {
            if ($series->custom) {
                // Custom: User has all existing clippers in DB
                return $series->clippers_count > 0 && $series->collected_count >= $series->clippers_count;
            } else {
                // Non-custom: User has all 4
                return $series->collected_count >= 4;
            }
        })->count();
    }
}