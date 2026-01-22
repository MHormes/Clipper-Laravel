<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ClipperService;
use App\Services\SeriesService;
use App\Models\Series;
use App\Models\Clipper;

class DashboardController extends Controller
{

    public function __construct(
        protected ClipperService $clipperService,
        protected SeriesService $seriesService
    ) {}
    
    
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $mySeriesCount = Series::whereHas('clippers.collections', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->count();

        $pendingSeriesCount = 0;
        $pendingClippersCount = 0;

        if ($user->isAdmin()) {
            $pendingSeriesCount = Series::pending()->count();
            $pendingClippersCount = Clipper::pending()->count();
        }

        return Inertia::render('Dashboard', [
            'recentSeries' => $this->seriesService->getSeriesCatalog($user, 8),
            'stats' => [
                ['label' => 'Total Series', 'value' => (string) Series::accepted()->count()],
                ['label' => 'Total Clippers', 'value' => (string) Clipper::accepted()->count()],
                ['label' => 'My Series', 'value' => (string) $mySeriesCount],
                ['label' => 'My Clippers', 'value' => (string) $request->user()->myCollection()->count()],
                ['label' => 'Completed Series', 'value' => (string) $this->seriesService->countCompletedSeries($request->user())],
            ],
            'pendingRequests' => [
                'series' => $pendingSeriesCount,
                'clippers' => $pendingClippersCount,
            ]
        ])->withViewData([
            'metaTitle' => 'Clipper-MS: Dashboard',
            'metaDescription' => 'View and manage your Clipper collection at a glance.',
            'metaImage' => url('/images/dashboard-og.png')
        ]);
    }
}
