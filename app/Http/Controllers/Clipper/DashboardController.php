<?php

namespace App\Http\Controllers\Clipper;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ClipperService;
use App\Services\SeriesService;
use App\Support\SeoMetadata;
use App\Models\Series;
use App\Http\Controllers\Controller;
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

        if (!$user) {
            // Serve a blank page with metadata for crawlers
            return Inertia::render('Dashboard', [
                'recentSeries' => [],
                'stats' => [],
            ])->withViewData(
                SeoMetadata::forDashboard()->toArray()
            );
        }

        $stats = [];
        $pendingRequests = ['series' => 0, 'clippers' => 0];

        if ($user) {
            $mySeriesCount = Series::whereHas('clippers.collections', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();

            $pendingSeriesCount = $user->isAdmin() ? Series::pending()->count() : 0;
            $pendingClippersCount = $user->isAdmin() ? Clipper::pending()->count() : 0;

            $stats = [
                ['label' => 'Total Clippers', 'value' => (string) Clipper::accepted()->count()],
                ['label' => 'Total Series', 'value' => (string) Series::accepted()->count()],
                ['label' => 'My Clippers', 'value' => (string) $user->myCollection()->count()],
                ['label' => 'My Series', 'value' => (string) $mySeriesCount],
                ['label' => 'Completed Series', 'value' => (string) $this->seriesService->countCompletedSeries($user)],
            ];

            $pendingRequests = [
                'series' => $pendingSeriesCount,
                'clippers' => $pendingClippersCount,
            ];
        }

        return Inertia::render('Dashboard', [
            'recentSeries' => $user ? $this->seriesService->getSeriesCatalog($user, 8) : [],
            'stats' => $stats,
            'pendingRequests' => $pendingRequests
        ])->withViewData(
            SeoMetadata::forDashboard()->toArray()
        );
    }
}
