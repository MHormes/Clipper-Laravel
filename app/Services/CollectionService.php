<?php

namespace App\Services;

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use App\Models\CollectedClipper;

class CollectionService
{
    /**
     * Get series that the user has started collecting.
     */
    public function getCollectedSeries(User $user, array $filters)
    {
        $query = Series::accepted()->whereHas('clippers.collections', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Search
        if (!empty($filters['search'])) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($filters['search']) . '%']);
        }

        // Sorting
        $sortCol = $filters['sortCol'] ?? 'created_at';
        $sortDir = ($filters['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortCol, $sortDir);

        return $query->withCount(['clippers' => fn($q) => $q->accepted()])
            ->withCount(['clippers as collected_clippers_count' => function ($q) use ($user) {
                $q->accepted()->whereHas('collections', fn($sq) => $sq->where('user_id', $user->id));
            }])
            ->paginate(20)
            ->through(fn($series) => [
                'id' => $series->id,
                'name' => $series->name,
                'image_data' => $series->image_data,
                'custom' => $series->custom,
                'collected_count' => $series->collected_clippers_count,
                'total_count' => $series->clippers_count,
            ]);
    }

    /**
     * Get all individual clippers owned by the user (Board View).
     */
    public function getAllOwnedClippers(User $user, ?string $search = null)
    {
        return $user->myCollection()
            ->whereHas('clipper', function ($q) use ($search) {
                $q->accepted();
                if ($search) {
                    $q->whereHas('series', function ($sq) use ($search) {
                        $sq->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                    });
                }
            })
            ->with(['clipper.series'])
            ->paginate(64)
            ->withQueryString()
            // ->orderBy('series_id', 'asc')
            ->through(fn($item) => [
                'id' => $item->clipper->id,
                'series_number' => $item->clipper->series_number,
                'image_data' => $item->clipper->image_data,
                'series' => [
                    'id' => $item->clipper->series->id,
                    'name' => $item->clipper->series->name,
                ],
                'created_at' => $item->created_at,
            ]);
    }

    /**
     * Get all owned clippers that have valid coordinates for map rendering.
     */
    public function getMapPins(User $user): array
    {
        return $user->myCollection()
            ->whereNotNull('location_bought')
            ->whereHas('clipper', fn($query) => $query->accepted())
            ->with(['clipper.series'])
            ->get()
            ->map(function (CollectedClipper $item) {
                $coords = $this->parseCoordinates($item->location_bought);

                if (!$coords || !$item->clipper || !$item->clipper->series) {
                    return null;
                }

                return [
                    'id' => $item->clipper->id,
                    'lat' => $coords['lat'],
                    'lon' => $coords['lon'],
                    'image_data' => $item->clipper->image_data,
                    'series' => [
                        'id' => $item->clipper->series->id,
                        'name' => $item->clipper->series->name,
                        'slug' => $item->clipper->series->slug,
                    ],
                    'series_number' => $item->clipper->series_number,
                    'location_bought' => $item->location_bought,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get a map of collected clipper details for a specific series.
     * Returns: { [clipper_id]: { notes: string, location_bought: string, ... } }
     */
    public function getCollectedClippersForSeries(Series $series, User $user): array
    {
        return $user->myCollection()
            ->whereIn('clipper_id', $series->clippers->pluck('id'))
            ->get()
            ->keyBy('clipper_id') // This turns the collection into an object keyed by clipper_id
            ->map(fn($item) => [
                'notes' => $item->notes,
                'location_bought' => $item->location_bought,
                'collected_at' => $item->created_at->format('Y-m-d'),
            ])
            ->toArray();
    }
    
    /**
     * Toggle a single clipper for a user.
     */
    public function toggleSingleClipper(User $user, Clipper $clipper): void
    {
        $existing = $user->myCollection()
            ->where('clipper_id', $clipper->id)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $user->myCollection()->create([
                'clipper_id' => $clipper->id
            ]);
        }
    }

    /**
     * Toggle the collection status of an entire series.
     * If the user has all clippers, remove them. 
     * If they have some, add the missing ones.
     */
    public function toggleEntireSeries(User $user, Series $series): void
    {
        $clipperIds = $series->clippers()->accepted()->pluck('id');
        
        $collectedCount = $user->myCollection()
            ->whereIn('clipper_id', $clipperIds)
            ->count();

        if ($collectedCount === $clipperIds->count()) {
            // User has everything; remove all
            $user->myCollection()->whereIn('clipper_id', $clipperIds)->delete();
        } else {
            // User is missing some; add the missing ones
            $existingIds = $user->myCollection()
                ->whereIn('clipper_id', $clipperIds)
                ->pluck('clipper_id')
                ->toArray();

            $missingIds = $clipperIds->diff($existingIds);

            foreach ($missingIds as $id) {
                $user->myCollection()->create(['clipper_id' => $id]);
            }
        }
    }

    /**
     * Update the notes or location for a specific clipper in the user's collection.
     */
    public function updateCollectionDetails(User $user, Clipper $clipper, array $data)
    {
        // Find the record in the collected_clippers table
        $collection = CollectedClipper::where('user_id', $user->id)
            ->where('clipper_id', $clipper->id)
            ->first();

        if (!$collection) {
            // If they haven't "hearted" it yet, we create the record now
            return CollectedClipper::create([
                'user_id' => $user->id,
                'clipper_id' => $clipper->id,
                'notes' => $data['notes'] ?? null,
                'location_bought' => $data['location_bought'] ?? null,
            ]);
        }

        return $collection->update([
            'notes' => $data['notes'],
            'location_bought' => $data['location_bought'],
        ]);
    }

    private function parseCoordinates(?string $coordinates): ?array
    {
        if (blank($coordinates)) {
            return null;
        }

        $parts = array_map('trim', explode(',', $coordinates));
        if (count($parts) !== 2) {
            return null;
        }

        $lat = filter_var($parts[0], FILTER_VALIDATE_FLOAT);
        $lon = filter_var($parts[1], FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lon === false) {
            return null;
        }

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return ['lat' => (float) $lat, 'lon' => (float) $lon];
    }
}
