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
    public function declineSeriesRequest(Series $series): void
    {
        $requester = User::find($series->requested_by);
        $seriesName = $series->name;

        $this->seriesService->deleteSeries($series);

        if ($requester) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::SeriesDeclined,
                new SeriesRequestDeclinedNotification($seriesName)
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
            $clipper->update(['accepted_by' => $adminUser->id]);
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
    public function declineClipper(Clipper $clipper): void
    {
        $requester = User::find($clipper->requested_by);
        $clipper->load('series');
        $seriesName = $clipper->series?->name ?? '';

        $this->clipperService->deleteClipper($clipper);

        if ($requester && $seriesName) {
            $this->emailNotificationService->notifyUser(
                $requester,
                EmailNotificationCategory::ClipperDeclined,
                new ClipperRequestDeclinedNotification($seriesName)
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
