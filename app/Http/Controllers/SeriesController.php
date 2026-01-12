<?php

namespace App\Http\Controllers;

use App\Services\ClipperService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;
use Illuminate\Validation\Rule;

class SeriesController extends Controller
{
    public function __construct(protected ClipperService $clipperService) {}

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
        $series->load(['clippers', 'creator:id,name']);

        $user = $request->user();

        return Inertia::render('series/Show', [
            'series' => $series,
            'userCollection' => $this->clipperService->getCollectedClippersForSeries($series, $user),
        ]);
    }

    public function index()
    {
        return Inertia::render('series/Index', [
            'series' => $this->clipperService->getSeriesCatalog()
        ]);
    }

    /**
     * Store a newly created series.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:series,name|max:255',
            'custom' => 'required',
            'image' => 'required|image|max:10240',
            'clippers' => [
                    'array',
                    'max:4',
                    function ($attribute, $value, $fail) {
                        $hasAtLeastOne = collect($value)->contains(function ($slot) {
                            return isset($slot['image']) && $slot['image'] instanceof \Illuminate\Http\UploadedFile;
                        });

                        if (!$hasAtLeastOne) {
                            // We can keep this, but ensure Vue looks for "clippers"
                            $fail('Please upload at least one clipper image to any of the slots.');
                        }
                    },
                ],
            'clippers.*.image' => 'nullable|image|max:10240',
        ]);

        $series = $this->clipperService->createSeriesWithClippers(
            $request->user(), 
            $request->all() 
        );

        return to_route('series.show', $series->id);
    }

    /**
     * Update the specified series.
     */
   public function update(Request $request, Series $series)
    {
        $request->validate([
            // Rule::unique ignores the current series ID so you can save without changing the name
            'name' => ['required', 'string', 'max:255', Rule::unique('series')->ignore($series->id)],
            'custom' => 'nullable',
            'image' => 'nullable|image|max:10240',
            'clippers' => 'array|max:4',
            'clippers.*.image' => 'nullable|image|max:10240',
        ]);

        // Pass the series object, the current user, and the data to the service
        $this->clipperService->updateSeries($series, $request->user(), $request->all());

        return to_route('series.show', $series->id)
            ->with('success', 'Series updated successfully!');
    }

    /**
     * Remove the specified series.
    */
   public function destroy(Request $request, Series $series)
    {

        $user = $request->user();
        // Safety check for role
        if ($user->role !== 'admin') {
            abort(403);
        }

        $this->clipperService->deleteSeries($series);

        return to_route('series.index')->with('success', 'Series and all associated images deleted.');
    }
}