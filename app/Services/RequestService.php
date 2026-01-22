<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Clipper;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        protected SeriesService $seriesService,
        protected ClipperService $clipperService,
        protected ImageService $imageService
    ) {}

    /**
     * Accept a series request fully (accept series and all its clippers).
     */
    public function acceptSeriesFull(Series $series, User $adminUser): void
    {
        DB::transaction(function () use ($series, $adminUser) {
            $series->update(['accepted_by' => $adminUser->id]);
            
            // Accept all pending clippers for this series
            $series->clippers()->pending()->update(['accepted_by' => $adminUser->id]);
        });
    }

    /**
     * Accept a series request partially (accept series and selected clippers).
     */
    public function acceptSeriesPartial(Series $series, User $adminUser, array $acceptedClipperIds): void
    {
        DB::transaction(function () use ($series, $adminUser, $acceptedClipperIds) {
            $series->update(['accepted_by' => $adminUser->id]);

            // Accept selected clippers
            Clipper::whereIn('id', $acceptedClipperIds)
                ->where('series_id', $series->id)
                ->update(['accepted_by' => $adminUser->id]);

            // Decline/Delete remaining pending clippers for this series
            $toDelete = $series->clippers()->pending()->get();
            foreach ($toDelete as $clipper) {
                $this->clipperService->deleteClipper($clipper);
            }
        });
    }

    /**
     * Decline a series request (delete everything).
     */
    public function declineSeriesRequest(Series $series): void
    {
        $this->seriesService->deleteSeries($series);
    }

    /**
     * Accept an individual clipper request.
     */
    public function acceptClipper(Clipper $clipper, User $adminUser): void
    {
        $clipper->update(['accepted_by' => $adminUser->id]);
    }

    /**
     * Decline/Delete an individual clipper request.
     */
    public function declineClipper(Clipper $clipper): void
    {
        $this->clipperService->deleteClipper($clipper);
    }
}
