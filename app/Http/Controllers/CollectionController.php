<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;
use App\Models\Clipper;
use App\Services\CollectionService;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
   public function __construct(protected CollectionService $collectionService) {}

    /**
     * Display a listing of the series the user has started collecting.
     */
    public function index(Request $request)
    {
        return Inertia::render('collection/Index', [
            'series' => $this->collectionService->getCollectedSeries(
                $request->user(), 
                $request->only(['search', 'sortCol', 'sortDir'])
            ),
            'filters' => $request->only(['search', 'sortCol', 'sortDir']),
        ]);
    }

    /**
     * Display the specified series with the user's collected clippers.
     */
   public function show(Request $request, Series $series)
    {
        $user = $request->user();

        // Load only clippers the user actually owns for this series
        $series->load(['clippers' => function ($query) use ($user) {
            $query->whereHas('collections', fn($q) => $q->where('user_id', $user->id))
                  ->with(['collections' => fn($q) => $q->where('user_id', $user->id)]);
        }]);

        return Inertia::render('collection/Show', [
            'series' => $series, 
            'userCollection' => $series->clippers->pluck('id')->toArray(),
        ]);
    }


    /**
     * Show ALL clippers owned by the user (Board View).
     */
    public function clippers(Request $request)
    {
        return Inertia::render('Collection/All', [
            'clippers' => $this->collectionService->getAllOwnedClippers($request->user()),
        ]);
    }
    
    public function update(Request $request, Clipper $clipper)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'location_bought' => 'nullable|string|max:255',
        ]);

        // Call the service
        $this->collectionService->updateCollectionDetails(
            $request->user(),
            $clipper,
            $validated
        );

        return back();
    }

    /**
     * Toggle the collection status of an entire series.
     */
    public function toggleCollection(Request $request, Series $series): RedirectResponse
    {
        $this->collectionService->toggleEntireSeries($request->user(), $series);
        return back();
    }

    /**
     * Toggle a clipper in the user's personal collection.
     */
    public function toggle(Request $request, Clipper $clipper): RedirectResponse
    {
        $this->collectionService->toggleSingleClipper($request->user(), $clipper);

        return back();
    }
}
