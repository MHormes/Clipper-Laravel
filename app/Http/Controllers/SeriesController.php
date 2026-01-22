<?php

namespace App\Http\Controllers;

use App\Services\ClipperService;
use App\Services\SeriesService;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;
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


    public function show(Request $request, Series $series)
    {
        $user = $request->user();

        // Prevent viewing pending series unless you are admin or the requester
        if ($series->accepted_by === null && !$user->isAdmin() && $series->requested_by !== $user->id) {
            abort(404);
        }

        // Eager load only the ACCEPTED clippers for public view
        // If the user IS an admin or the requester, we might want to show pending too?
        // But the requirement says "Only accepted series and clippers are displayed publicly."
        // Let's stick to showing only accepted clippers on the main show page.
        $series->load([
            'clippers' => fn($q) => $q->accepted(),
            'requester:id,name'
        ]);

        return Inertia::render('series/Show', [
            'series' => $series,
            'userCollection' => $this->collectionService->getCollectedClippersForSeries($series, $user),
        ]);
    }

    public function index(Request $request)
    {
        return Inertia::render('series/Index', [
            'series' => $this->seriesService->getSeriesCatalog(
                $request->user(), 
                null, 
                $request->input('search'), 
                $request->input('sortCol'), 
                $request->input('sortDir')
            ),
            'filters' => $request->only(['search', 'sortCol', 'sortDir'])
        ]);
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

        return to_route('series.show', $series->id);
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

        return to_route('series.show', $series->id)
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

        return to_route('series.show', $series->id)
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