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
            $pendingClippers = $series->clippers()->pending()->get();

            $series->update(['accepted_by' => $adminUser->id]);

            foreach ($pendingClippers as $clipper) {
                $clipper->update(['accepted_by' => $adminUser->id]);
                $this->autoAddRequestedClipperToCollection($clipper);
            }
        });
    }

    /**
     * Accept a series request partially (accept series and selected clippers).
     */
    public function acceptSeriesPartial(Series $series, User $adminUser, array $acceptedClipperIds): void
    {
        DB::transaction(function () use ($series, $adminUser, $acceptedClipperIds) {
            $series->update(['accepted_by' => $adminUser->id]);

            $acceptedClippers = Clipper::whereIn('id', $acceptedClipperIds)
                ->where('series_id', $series->id)
                ->get();

            foreach ($acceptedClippers as $clipper) {
                $clipper->update(['accepted_by' => $adminUser->id]);
                $this->autoAddRequestedClipperToCollection($clipper);
            }

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
        DB::transaction(function () use ($clipper, $adminUser) {
            $clipper->update(['accepted_by' => $adminUser->id]);
            $this->autoAddRequestedClipperToCollection($clipper);
        });
    }

    /**
     * Decline/Delete an individual clipper request.
     */
    public function declineClipper(Clipper $clipper): void
    {
        $this->clipperService->deleteClipper($clipper);
    }

    protected function autoAddRequestedClipperToCollection(Clipper $clipper): void
    {
        if (!$clipper->auto_add_to_collection) {
            return;
        }

        $requester = User::find($clipper->requested_by);

        if (!$requester) {
            return;
        }

        $requester->myCollection()->firstOrCreate([
            'clipper_id' => $clipper->id,
        ]);
    }
}
