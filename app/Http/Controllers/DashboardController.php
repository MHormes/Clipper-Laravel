<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ClipperService;
use App\Models\Series;

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
        return Inertia::render('Dashboard', [
            'recentSeries' => $this->clipperService->getSeriesCatalog(8),
            'stats' => [
                ['label' => 'Total Series', 'value' => Series::count()],
                ['label' => 'My Clippers', 'value' => $request->user()->myCollection()->count()],
                ['label' => 'Completed Series', 'value' => '0'],
            ]
        ]);
    }
}
