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
        // Eager load the clippers associated with this series
        $series->load(['clippers', 'requester:id,name']);

        $user = $request->user();

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
        $series = $this->seriesService->createSeriesWithClippers(
            $request->user(), 
            $request->validated() 
        );

        return to_route('series.show', $series->id);
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