<?php

namespace App\Http\Controllers\Clipper;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;
use App\Models\Clipper;
use App\Services\CollectionService;
use App\Support\SeoMetadata;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
   public function __construct(protected CollectionService $collectionService) {}


    /**
     * Display a map with all collected clippers
     */
    public function mapview(Request $request)
    {
        $user = $request->user();

        return Inertia::render('collection/map/Index', [
            'markers' => $user ? $this->collectionService->getMapPins($user) : [],
            'tileConfig' => [
                'url' => config('services.maps.tile_url'),
                'attribution' => config('services.maps.attribution'),
            ],
        ])->withViewData(
            SeoMetadata::forMapView()->toArray()
        );
    }

    /**
     * Show ALL clippers owned by the user (Board View).
     */
    public function clippers(Request $request)
    {
        $user = $request->user();

        return Inertia::render('collection/All', [
            'clippers' => $user ? $this->collectionService->getAllOwnedClippers(
                $user,
                $request->input('search')
            ) : [],
            'filters' => $user ? $request->only(['search']) : [],
        ])->withViewData(
            SeoMetadata::forClippersBoard()->toArray()
        );
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

    /**
     * Return a lightweight list of all collected clippers for the copy picker.
     */
    public function list(Request $request)
    {
        return response()->json(
            $this->collectionService->getCollectedClipperList($request->user())
        );
    }

    /**
     * Copy notes/location from one collected clipper to selected others.
     */
    public function copyTo(Request $request, Clipper $clipper)
    {
        $validated = $request->validate([
            'clipper_ids'   => 'required|array|min:1',
            'clipper_ids.*' => 'uuid|exists:clippers,id',
            'fields'        => 'required|array|min:1',
            'fields.*'      => 'in:notes,location_bought',
        ]);

        $this->collectionService->copyCollectionInfo(
            $request->user(),
            $clipper,
            $validated['clipper_ids'],
            $validated['fields']
        );

        return back();
    }
}
