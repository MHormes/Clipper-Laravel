<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SeriesService;
use App\Services\ClipperService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PendingRequestController extends Controller
{
    public function __construct(
        protected SeriesService $seriesService,
        protected ClipperService $clipperService
    ) {}

    /**
     * Display a listing of the user's pending series requests.
     */
    public function seriesIndex(Request $request)
    {
        return Inertia::render('requests/PendingSeriesIndex', [
            'series' => $this->seriesService->getPendingSeriesRequestsForUser($request->user())
        ]);
    }

    /**
     * Display a listing of the user's pending clipper requests.
     */
    public function clippersIndex(Request $request)
    {
        return Inertia::render('requests/PendingClippersIndex', [
            'groupedClippers' => $this->clipperService->getPendingClipperRequestsForUser($request->user())
        ]);
    }
}
