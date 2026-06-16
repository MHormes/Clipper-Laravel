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

        return Inertia::render('Dashboard', [
            'recentSeries' => $user ? $this->seriesService->getSeriesCatalog($user, 8) : [],
            
            'stats' => $this->resolveStats($user),
            
            'pendingRequests' => [
                'series' => $user?->isAdmin() ? Series::pending()->count() : 0,
                'clippers' => $user?->isAdmin() ? Clipper::where(fn($q) => $q->pending()->orWhereNotNull('pending_image_data'))->count() : 0,
            ]
        ])->withViewData(SeoMetadata::forDashboard()->toArray());
    }

    private function resolveStats($user): array
    {
        if (!$user) return [];

        $mySeriesCount = Series::whereHas('clippers.collections', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();

        return [
            ['label' => 'Total Clippers', 'value' => (string) Clipper::accepted()->count()],
            ['label' => 'Total Series', 'value' => (string) Series::accepted()->count()],
            ['label' => 'My Clippers', 'value' => (string) $user->myCollection()->count()],
            ['label' => 'My Series', 'value' => (string) $mySeriesCount],
            ['label' => 'Completed Series', 'value' => (string) $this->seriesService->countCompletedSeries($user)],
        ];
    }
}
