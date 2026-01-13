<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ClipperService;
use App\Models\Series;
use App\Models\Clipper;

class DashboardController extends Controller
{

    public function __construct(
        protected ClipperService $clipperService
    ) {}
    
    
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $mySeriesCount = Series::whereHas('clippers.collections', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->count();

        return Inertia::render('Dashboard', [
            'recentSeries' => $this->clipperService->getSeriesCatalog(8),
            'stats' => [
                ['label' => 'Total Series', 'value' => (string) Series::count()],
                ['label' => 'Total Clippers', 'value' => (string) Clipper::count()],
                ['label' => 'My Series', 'value' => (string) $mySeriesCount],
                ['label' => 'My Clippers', 'value' => (string) $request->user()->myCollection()->count()],
                ['label' => 'Completed Series', 'value' => (string) $this->clipperService->countCompletedSeries($request->user())],
            ]
        ]);
    }
}
