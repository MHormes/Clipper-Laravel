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
        $query = Series::whereHas('clippers.collections', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Search
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        // Sorting
        $sortCol = $filters['sortCol'] ?? 'created_at';
        $sortDir = ($filters['sortDir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortCol, $sortDir);

        return $query->withCount('clippers')
            ->withCount(['clippers as collected_clippers_count' => function ($q) use ($user) {
                $q->whereHas('collections', fn($sq) => $sq->where('user_id', $user->id));
            }])
            ->paginate(12)
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
    public function getAllOwnedClippers(User $user)
    {
        return $user->myCollection()
            ->with(['clipper.series'])
            ->latest()
            ->paginate(16)
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
        $clipperIds = $series->clippers()->pluck('id');
        
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
}