<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;

class CollectionController extends Controller
{
    /**
     * Display a listing of the series the user has started collecting.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $series = Series::whereHas('clippers.collections', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->withCount(['clippers as collected_clippers_count' => function ($query) use ($user) {
            $query->whereHas('collections', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }])
        ->withCount('clippers') // Total clippers in the series
        ->paginate(12)
        ->through(function ($series) {
            return [
                'id' => $series->id,
                'name' => $series->name,
                'image_data' => $series->image_data, // Using accessors if available, or direct mapping
                'custom' => $series->custom,
                'collected_count' => $series->collected_clippers_count,
                'total_count' => $series->clippers_count,
            ];
        });

        return Inertia::render('Collection/Index', [
            'series' => $series,
        ]);
    }

    /**
     * Display the specified series with the user's collected clippers.
     */
    public function show(Request $request, Series $series)
    {
        $user = $request->user();

        // Ensure the user actually has something from this series? 
        // Or just show it regardless? Let's show it, filtering clippers.
        
        $series->load(['clippers' => function ($query) use ($user) {
            $query->whereHas('collections', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }]);

        // Transform clippers for the view
        $clippers = $series->clippers->map(function ($clipper) {
            return [
                'id' => $clipper->id,
                'series_number' => $clipper->series_number,
                'image_data' => $clipper->image_data,
            ];
        });

        return Inertia::render('Collection/Show', [
            'series' => [
                'id' => $series->id,
                'name' => $series->name,
                'image_data' => $series->image_data,
                'custom' => $series->custom,
                'clippers' => $clippers,
            ],
            // We can reuse the same toggle logic if we want, or add specific collection routes.
            // For now, we will use the existing toggle routes on the frontend, 
            // but we need to know the User's collection IDs for the "heart" state.
            // Actually, in this view, ALL displayed items are in the collection.
            // So we just need the IDs to confirm or toggle them off.
            'userCollection' => $clippers->pluck('series_number')->toArray(),
        ]);
    }

    /**
     * Show ALL clippers owned by the user (Board View).
     */
    public function clippers(Request $request)
    {
        $user = $request->user();

        $clippers = $user->myCollection()
            ->with(['clipper.series']) // Eager load clipper and its series
            ->latest('date_added')
            ->paginate(50) 
            ->through(function ($collectionItem) {
                $clipper = $collectionItem->clipper;
                return [
                    'id' => $clipper->id,
                    'series_number' => $clipper->series_number,
                    'image_data' => $clipper->image_data,
                    'series' => [
                        'id' => $clipper->series->id,
                        'name' => $clipper->series->name,
                    ],
                    'date_added' => $collectionItem->date_added,
                ];
            });

        return Inertia::render('Collection/All', [
            'clippers' => $clippers,
        ]);
    }
}
