<?php

namespace App\Services;

use App\Enums\EmailNotificationCategory;
use App\Models\Series;
use App\Models\Clipper;
use App\Models\User;
use App\Notifications\Requests\ClipperRequestAcceptedNotification;
use App\Notifications\Requests\ClipperRequestDeclinedNotification;
use App\Notifications\Requests\SeriesRequestAcceptedNotification;
use App\Notifications\Requests\SeriesRequestDeclinedNotification;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        protected SeriesService $seriesService,
        protected ClipperService $clipperService,
        protected ImageService $imageService,
        protected EmailNotificationService $emailNotificationService
    ) {}

    /**
     * Accept a series request fully (accept series and all its clippers).
     */
    public function acceptSeriesFull(Series $series, User $adminUser): void
    {
        $requesterId = $series->requested_by;

        DB::transaction(function () use ($series, $adminUser) {
            $pendingClippers = $series->clippers()->pending()->get();

            $series->update(['accepted_by' => $adminUser->id]);

            foreach ($pendingClippers as $clipper) {
                $clipper->update(['accepted_by' => $adminUser->id]);
                $this->autoAddRequestedClipperToCollection($clipper);
            }
        });

        $requester = User::find($requesterId);
        if ($requester) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::SeriesAccepted,
                new SeriesRequestAcceptedNotification($series)
            );
        }
    }

    /**
     * Accept a series request partially (accept series and selected clippers).
     */
    public function acceptSeriesPartial(Series $series, User $adminUser, array $acceptedClipperIds): void
    {
        $requesterId = $series->requested_by;

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

        $requester = User::find($requesterId);
        if ($requester) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::SeriesAccepted,
                new SeriesRequestAcceptedNotification($series)
            );
        }
    }

    /**
     * Decline a series request (delete everything).
     */
    public function declineSeriesRequest(Series $series, string $reason = ''): void
    {
        $requester = User::find($series->requested_by);
        $seriesName = $series->name;

        $this->seriesService->deleteSeries($series);

        if ($requester) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::SeriesDeclined,
                new SeriesRequestDeclinedNotification($seriesName, $reason)
            );
        }
    }

    /**
     * Accept an individual clipper request.
     */
    public function acceptClipper(Clipper $clipper, User $adminUser): void
    {
        $requesterId = $clipper->requested_by;
        $clipper->load('series');
        $series = $clipper->series;

        DB::transaction(function () use ($clipper, $adminUser) {
            if ($clipper->getRawOriginal('pending_image_data')) {
                // Replacement request: swap the staged image into the live slot.
                $this->imageService->deleteImage($clipper->getRawOriginal('image_data'));

                $clipper->update([
                    'image_data'         => $clipper->getRawOriginal('pending_image_data'),
                    'pending_image_data' => null,
                    'accepted_by'        => $adminUser->id,
                ]);
            } else {
                // New clipper request: just mark as accepted.
                $clipper->update(['accepted_by' => $adminUser->id]);
            }

            $this->autoAddRequestedClipperToCollection($clipper);
        });

        $requester = User::find($requesterId);
        if ($requester && $series) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::ClipperAccepted,
                new ClipperRequestAcceptedNotification($series)
            );
        }
    }

    /**
     * Decline/Delete an individual clipper request.
     */
    public function declineClipper(Clipper $clipper, string $reason = ''): void
    {
        $requester = User::find($clipper->requested_by);
        $clipper->load('series');
        $seriesName = $clipper->series?->name ?? '';

        $isReplacement = $clipper->pending_image_data
            || ($clipper->getRawOriginal('original_accepted_by') && !$clipper->accepted_by);

        if ($isReplacement) {
            // Replacement request: discard staged image and restore original accepted state.
            if ($clipper->pending_image_data) {
                $this->imageService->deleteImage($clipper->getRawOriginal('pending_image_data'));
            }

            $updates = ['pending_image_data' => null, 'original_accepted_by' => null];

            // Restore accepted_by if it was cleared (handles data from earlier code revisions).
            if (!$clipper->accepted_by && $clipper->getRawOriginal('original_accepted_by')) {
                $updates['accepted_by'] = $clipper->getRawOriginal('original_accepted_by');
            }

            $clipper->update($updates);
        } else {
            // New clipper request: delete the entire record.
            $this->clipperService->deleteClipper($clipper);
        }

        if ($requester && $seriesName) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::ClipperDeclined,
                new ClipperRequestDeclinedNotification($seriesName, $reason)
            );
        }
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
