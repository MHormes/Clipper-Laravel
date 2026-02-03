<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Clipper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\CollectedClipper;

class SeriesService
{

    public function __construct(protected ImageService $imageService, protected ClipperService $clipperService) {}

    /**
     * Create a brand new series and its clippers.
     */
    public function createSeriesWithClippers($user, array $data, bool $isRequest = false)
    {
        return DB::transaction(function () use ($user, $data, $isRequest) {
            $seriesPath = null;
            if (isset($data['image'])) {
                $seriesPath = $this->imageService->uploadImage($data['image'], 'series');
            }

            $series = Series::create([
                'name'       => $data['name'],
                'custom'     => filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN),
                'image_data' => $seriesPath, // Stores: "series/filename.jpg"
                'requested_by' => $user->id,
                'accepted_by' => $isRequest ? null : $user->id,
            ]);

            $this->clipperService->createClippersInBatch($series, $data['clippers'], $user->id, $isRequest);

            return $series;
        });
    }

    /**
     * Update an existing series and manage clipper slots.
     */
    public function updateSeries(Series $series, $user, array $data)
    {
        return DB::transaction(function () use ($series, $user, $data) {
            // Update basic info
            $series->name = $data['name'];
            $series->custom = filter_var($data['custom'], FILTER_VALIDATE_BOOLEAN);

            // Handle Series Image Update
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $this->imageService->deleteImage($series->getRawOriginal('image_data'));
                $series->image_data = $this->imageService->uploadImage($data['image'], 'series');
                // When admin updates, we ensure it's marked as accepted by them
                $series->accepted_by = $user->id;
            }
            
            $series->save();

            // Delegate all clipper logic to the ClipperService
            $this->clipperService->syncClippers($series, $data, $user->id);

            return $series;
        });
    }

    /**
     * Delete a series and all associated assets.
     */
    public function deleteSeries(Series $series)
    {
        return DB::transaction(function () use ($series) {
            // 1. Delete all clippers using the specialized service
            // This handles image deletion and pivot cleanup automatically
            foreach ($series->clippers as $clipper) {
                $this->clipperService->deleteClipper($clipper);
            }

            // 2. Delete the series image
            $this->imageService->deleteImage($series->getRawOriginal('image_data'));

            // 3. Delete the series record
            return $series->delete();
        });
    }


    /**
     * Get a paginated list of series for the catalog.
     * Includes the count of clippers the user has collected in that series.
     */
    public function getSeriesCatalog(User $user, ?int $limit = null, array $filters = [])
    {
        $column = filled($filters['sortCol'] ?? null) ? $filters['sortCol'] : 'created_at';
        $direction = in_array(strtolower($filters['sortDir'] ?? ''), ['asc', 'desc']) ? $filters['sortDir'] : 'desc';

        $query = Series::accepted()
            ->withCount(['clippers' => fn($q) => $q->accepted()])
            ->with(['requester:id,name'])
            // We use an alias to clearly distinguish "Total" vs "Owned"
            ->withCount(['clippers as collected_clippers_count' => function ($query) use ($user) {
                $query->accepted()->whereHas('collections', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }]);

        // Search
        if (!empty($filters['search'])) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($filters['search']) . '%']);
        }

        // Type Filter (Official/Custom)
        $type = $filters['type'] ?? 'all';
        if ($type === 'official') {
            $query->where('custom', 0);
        } elseif ($type === 'custom') {
            $query->where('custom', 1);
        }

        // Status Filter (Collected/Completed)
        $filter = $filters['filter'] ?? 'all';
        if ($filter === 'collected') {
            // Collected Any: Show series where user has >=1 clipper
            $query->whereHas('clippers.collections', fn($q) => $q->where('user_id', $user->id));
        } elseif ($filter === 'none') {
            // Collected None: Show series where user has 0 clippers
            $query->whereDoesntHave('clippers.collections', fn($q) => $q->where('user_id', $user->id));
        } elseif ($filter === 'completed') {
            $query->where(function ($q) use ($user) {
                // Official Series (custom = 0): User has collected 4 or more clippers
                $q->where(function ($q) use ($user) {
                    $q->where('series.custom', false)
                        ->whereHas('clippers', function ($q) use ($user) {
                            $q->accepted()->whereHas('collections', fn($q) => $q->where('user_id', $user->id));
                        }, '>=', 4);
                })
                // Custom Series (custom = 1): User has collected ALL accepted clippers (min 1)
                ->orWhere(function ($q) use ($user) {
                    $q->where('series.custom', true)
                        ->whereHas('clippers', fn($q) => $q->accepted())
                        ->whereDoesntHave('clippers', function ($q) use ($user) {
                            $q->accepted()->whereDoesntHave('collections', fn($q) => $q->where('user_id', $user->id));
                        });
                });
            });
        }

        $query->orderBy($column, $direction);

        return $limit ? $query->limit($limit)->get() : $query->paginate(20)->withQueryString();
    }

    /**
     * Get pending series requests for admin review.
     */
    public function getPendingSeriesRequests()
    {
        return Series::pending()
            ->with(['requester', 'clippers'])
            ->withCount('clippers')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Count the number of completed series for a user.
     * Used as stats on the dashboard.
     */
    public function countCompletedSeries(User $user): int
    {
        $seriesStats = Series::accepted()
            ->withCount(['clippers' => fn($q) => $q->accepted()])
            ->withCount(['clippers as collected_count' => function ($query) use ($user) {
                $query->accepted()->whereHas('collections', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }])->get();

        return $seriesStats->filter(function ($series) {
            return $series->custom 
                ? ($series->clippers_count > 0 && $series->collected_count >= $series->clippers_count)
                : ($series->collected_count >= 4);
        })->count();
    }
}