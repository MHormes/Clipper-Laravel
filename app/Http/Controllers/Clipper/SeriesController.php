<?php

namespace App\Http\Controllers\Clipper;

use App\Services\ClipperService;
use App\Services\SeriesService;
use App\Services\CollectionService;
use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\Series;
use App\Models\User;
use App\Http\Requests\StoreSeriesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SeriesController extends Controller
{
    public function __construct(protected ClipperService $clipperService, protected SeriesService $seriesService, protected CollectionService $collectionService) {}

    /**
    * Show the form for creating a new series.
     */
    public function create()
    {
        return Inertia::render('series/Create');
    }

    /**
    * Show the form for editing an existing series.
     */
    public function edit(Series $series)
    {
        $series->load('clippers');
        return Inertia::render('series/Edit', [
            'series' => $series
        ]);
    }


    public function show(Request $request, Series $series, $slug = null)
    {
        $user = $request->user();

        // Prevent viewing pending series unless you are admin
        // This check must happen BEFORE the slug redirect to avoid leaking existence via 301
        $isAdmin = $user && $user->isAdmin();
        
        if ($series->accepted_by === null && !$isAdmin) {
            abort(404);
        }

        if ($slug !== $series->slug) {
            return to_route('series.show', ['series' => $series->id, 'slug' => $series->slug], 301);
        }

        if($user){
            $series->load([
                'clippers' => fn($q) => $q->accepted(),
                'requester:id,name'
            ]);
        }
        
        return Inertia::render('series/Show', [
            'series' => $series,
            'userCollection' => $user ? $this->collectionService->getCollectedClippersForSeries($series, $user) : [],
            'canManageCollection' => (bool) $user,
            'collectionOwnerName' => $user?->name ?? 'Guest',
            'profileUserId' => null,
        ])->withViewData(
            SeoMetadata::forSeries($series)->toArray()
        );
    }

    public function showFromProfile(Request $request, User $user, Series $series, $slug = null)
    {
        $viewer = $request->user();
        $isAdmin = $viewer && $viewer->isAdmin();

        if ($series->accepted_by === null && !$isAdmin) {
            abort(404);
        }

        if ($slug !== $series->slug) {
            return to_route('users.series.show', [
                'user' => $user->id,
                'series' => $series->id,
                'slug' => $series->slug,
            ], 301);
        }

        $series->load([
            'clippers' => fn($query) => $query->accepted(),
            'requester:id,name',
        ]);

        $canManageCollection = $viewer && $viewer->id === $user->id;

        return Inertia::render('series/Show', [
            'series' => $series,
            'userCollection' => $this->collectionService->getCollectedClippersForSeries($series, $user),
            'canManageCollection' => $canManageCollection,
            'collectionOwnerName' => $user->name,
            'profileUserId' => $user->id,
        ])->withViewData(
            SeoMetadata::forSeries($series)->toArray()
        );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        return Inertia::render('series/Index', [
            'series' => $user ? $this->seriesService->getSeriesCatalog(
                $user,
                null, 
                $request->only(['search', 'sortCol', 'sortDir', 'filter', 'type'])
            ) : [],
            'filters' => $user ? array_merge(
                $request->only(['search', 'sortCol', 'sortDir']),
                [
                    'filter' => $request->input('filter', 'all'),
                    'type' => $request->input('type', 'all')
                ]
            ) : ['filter' => 'all', 'type' => 'all']
        ])->withViewData(
            SeoMetadata::forSeriesIndex()->toArray()
        );
    }

   /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeriesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isRequest = !$user->isAdmin();

        $series = $this->seriesService->createSeriesWithClippers(
            $user, 
            $request->validated(),
            $isRequest
        );

        if ($isRequest) {
            return to_route('series.index')
                ->with('success', 'Your series request has been submitted and is pending review.');
        }

        return to_route('series.show', ['series' => $series->id, 'slug' => $series->slug]);
    }

    /**
     * Show the form for requesting clippers for an existing series.
     */
    public function requestClippers(Series $series)
    {
        $series->load('clippers');
        return Inertia::render('series/RequestClippers', [
            'series' => $series
        ]);
    }

    /**
     * Store a clipper request for an existing series.
     */
    public function storeClipperRequest(StoreSeriesRequest $request, Series $series): RedirectResponse
    {
        $this->clipperService->syncClippers($series, $request->validated(), $request->user()->id, true);

        return to_route('series.show', ['series' => $series->id, 'slug' => $series->slug])
            ->with('success', 'Your clipper requests have been submitted and are pending review.');
    }

    /**
     * Update the specified series.
     */
    public function update(StoreSeriesRequest $request, Series $series): RedirectResponse
    {
        Log::info($request);
        Log::info($series);
        $this->seriesService->updateSeries($series, $request->user(), $request->validated());

        return to_route('series.show', ['series' => $series->id, 'slug' => $series->slug])
            ->with('success', 'Series updated successfully!');
    }

    /**
     * Remove the specified series.
    */
    public function destroy(Request $request, Series $series)
    {

        $this->seriesService->deleteSeries($series);

        return to_route('series.index')
            ->with('success', 'Series and all associated images deleted.');
    }
}
