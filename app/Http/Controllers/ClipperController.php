<?php

namespace App\Http\Controllers;

use App\Services\ClipperService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Clipper;
use Illuminate\Http\RedirectResponse;

class ClipperController extends Controller
{
    public function __construct(protected ClipperService $clipperService) {}

    
    /**
     * Toggle a clipper in the user's personal collection.
     */
    public function toggle(Request $request, Clipper $clipper): RedirectResponse
    {
        $user = $request->user();

        // Check if the user already has this clipper in their 'myCollection'
        // 'clipper_id' is the foreign key in your CollectedClipper model
        $existing = $user->myCollection()
            ->where('clipper_id', $clipper->id)
            ->first();

        if ($existing) {
            // If it exists, remove it (Uncollect)
            $existing->delete();
        } else {
            // If it doesn't exist, add it (Collect)
            $user->myCollection()->create([
                'clipper_id' => $clipper->id,
                'date_added' => now(),
            ]);
        }

        // back() tells Inertia to stay on the same page and refresh the props
        return back();
    }
}