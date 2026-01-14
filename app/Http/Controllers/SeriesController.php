<?php

namespace App\Http\Controllers;

use App\Services\ClipperService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Series;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

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
        $series->load(['clippers', 'requester:id,name']);

        $user = $request->user();

        return Inertia::render('series/Show', [
            'series' => $series,
            'userCollection' => $this->clipperService->getCollectedClippersForSeries($series, $user),
        ]);
    }

    public function index(Request $request)
    {
        return Inertia::render('series/Index', [
            'series' => $this->clipperService->getSeriesCatalog(
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
                $request->boolean('custom') ? 'max:100' : 'max:4',
                function ($attribute, $value, $fail) use ($request) {
                    $isCustom = $request->boolean('custom');
                    $clippers = collect($value);

                    if ($isCustom) {
                        if ($clippers->isEmpty()) {
                            $fail('Custom series must have at least one clipper.');
                            return;
                        }
                        $allFilled = $clippers->every(fn($slot) => isset($slot['image']) && $slot['image'] instanceof \Illuminate\Http\UploadedFile);
                        if (!$allFilled) {
                            $fail('For custom series, you must provide an image for every slot you have added.');
                        }
                    } else {
                        $hasAtLeastOne = $clippers->contains(fn($slot) => isset($slot['image']) && $slot['image'] instanceof \Illuminate\Http\UploadedFile);
                        if (!$hasAtLeastOne) {
                            $fail('Please upload at least one clipper image.');
                        }
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
            'clippers' => [
                'array',
                $request->boolean('custom') ? 'max:100' : 'max:4',
                function ($attribute, $value, $fail) use ($request, $series) {
                    $isCustom = $request->boolean('custom');
                    $clippersArray = collect($value);

                    if ($isCustom) {
                        if ($clippersArray->isEmpty()) {
                            $fail('Custom series must have at least one clipper.');
                            return;
                        }

                        // Ensure every slot has either an existing ID or a newly uploaded image
                        $allValid = $clippersArray->every(function ($slot) {
                            $hasId = isset($slot['id']) && !empty($slot['id']);
                            $hasNewImage = isset($slot['image']) && $slot['image'] instanceof \Illuminate\Http\UploadedFile;
                            return $hasId || $hasNewImage;
                        });

                        if (!$allValid) {
                            $fail('All clipper slots in a custom series must have an image.');
                        }
                    } else {
                        // For standard series, we don't strictly enforce all 4 have images
                    }
                },
            ],
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

    /**
     * Toggle all clippers in a series for the current user.
     */
    public function toggleCollection(Request $request, Series $series): RedirectResponse
    {
        $user = $request->user();
        $clipperIds = $series->clippers()->pluck('id');
        
        // Find how many of these clippers are already in the user's collection
        $collectedCount = $user->myCollection()
            ->whereIn('clipper_id', $clipperIds)
            ->count();

        if ($collectedCount === $clipperIds->count()) {
            // Uncollect all if all are already collected
            $user->myCollection()->whereIn('clipper_id', $clipperIds)->delete();
        } else {
            // Collect all missing clippers
            $existingIds = $user->myCollection()
                ->whereIn('clipper_id', $clipperIds)
                ->pluck('clipper_id');

            $missingIds = $clipperIds->diff($existingIds);

            foreach ($missingIds as $id) {
                $user->myCollection()->create([
                    'clipper_id' => $id,
                    'date_added' => now(),
                ]);
            }
        }

        return back();
    }
}