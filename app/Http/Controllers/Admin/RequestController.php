<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\Clipper;
use App\Services\SeriesService;
use App\Services\ClipperService;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class RequestController extends Controller
{
    public function __construct(
        protected SeriesService $seriesService,
        protected ClipperService $clipperService,
        protected RequestService $requestService
    ) {}

    /**
     * Display a listing of pending series requests.
     */
    public function pendingSeriesIndex()
    {
        return Inertia::render('admin/requests/PendingSeriesIndex', [
            'series' => $this->seriesService->getPendingSeriesRequests()
        ]);
    }

    /**
     * Display a pending series request for review.
     */
    public function pendingSeriesShow(Series $series)
    {
        $series->load(['clippers' => fn($q) => $q->pending(), 'requester']);

        return Inertia::render('admin/requests/PendingSeriesReview', [
            'series' => $series
        ]);
    }

    /**
     * Accept a series request (full or partial).
     */
    public function acceptSeries(Request $request, Series $series): RedirectResponse
    {
        $request->validate([
            'mode' => 'required|in:full,partial',
            'clipper_ids' => 'required_if:mode,partial|array'
        ]);

        if ($request->mode === 'full') {
            $this->requestService->acceptSeriesFull($series, $request->user());
        } else {
            $this->requestService->acceptSeriesPartial($series, $request->user(), $request->clipper_ids);
        }

        return to_route('admin.requests.series.index')
            ->with('success', 'Series request processed successfully.');
    }

    /**
     * Decline a series request.
     */
    public function declineSeries(Series $series): RedirectResponse
    {
        $this->requestService->declineSeriesRequest($series);

        return to_route('admin.requests.series.index')
            ->with('success', 'Series request declined.');
    }

    /**
     * Display a listing of pending clipper requests for existing series.
     */
    public function pendingClippersIndex()
    {
        return Inertia::render('admin/requests/PendingClippersIndex', [
            'groupedClippers' => $this->clipperService->getPendingClipperRequests()
        ]);
    }

    /**
     * Accept an individual clipper request.
     */
    public function acceptClipper(Request $request, Clipper $clipper): RedirectResponse
    {
        $this->requestService->acceptClipper($clipper, $request->user());

        return back()->with('success', 'Clipper accepted.');
    }

    /**
     * Decline an individual clipper request.
     */
    public function declineClipper(Clipper $clipper): RedirectResponse
    {
        $this->requestService->declineClipper($clipper);

        return back()->with('success', 'Clipper request declined.');
    }
}
