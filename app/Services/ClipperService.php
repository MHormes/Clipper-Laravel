<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Clipper;
use Illuminate\Http\UploadedFile;

class ClipperService
{
    public function __construct(protected ImageService $imageService) {}

    /**
     * Entry point for updating clippers within a series.
     */
    public function syncClippers(Series $series, array $data, $userId): void
    {
        // 1. Delete requested clippers
        if (!empty($data['deleted_ids'])) {
            $this->deleteClippersById($data['deleted_ids']);
        }

        // 2. Process the clippers array (Create or Update)
        if (isset($data['clippers']) && is_array($data['clippers'])) {
            foreach ($data['clippers'] as $index => $clipperData) {
                $this->syncClipperSlot($series, $clipperData, $index + 1, $userId);
            }
        }

        // 3. Sequential re-indexing for custom sets
        if ($series->custom) {
            $this->reindexSeries($series);
        }
    }

    /**
     * Determines if a slot needs a new Clipper or an Update to an existing one.
     */
    protected function syncClipperSlot(Series $series, array $clipperData, int $slotNumber, $userId): void
    {
        // If no new image is provided, we don't need to do anything for this slot
        if (!isset($clipperData['image']) || !($clipperData['image'] instanceof UploadedFile)) {
            return;
        }

        $existingClipper = $series->clippers()->where('series_number', $slotNumber)->first();

        if ($existingClipper) {
            $this->updateClipper($existingClipper, $clipperData['image'], $userId);
        } else {
            $this->createClipper($series, $clipperData['image'], $slotNumber, $userId);
        }
    }

    /**
     * Create a single Clipper.
     */
    public function createClipper(Series $series, UploadedFile $image, int $slotNumber, $userId): Clipper
    {
        $path = $this->imageService->uploadImage($image, 'clippers');

        return $series->clippers()->create([
            'series_number' => $slotNumber,
            'image_data'    => $path,
            'requested_by'  => $userId,
            'accepted_by'   => $userId,
        ]);
    }

    /**
     * Update a single Clipper and swap images.
     */
    public function updateClipper(Clipper $clipper, UploadedFile $image, $userId): bool
    {
        // Delete old physical file
        $this->imageService->deleteImage($clipper->getRawOriginal('image_data'));

        $newPath = $this->imageService->uploadImage($image, 'clippers');

        return $clipper->update([
            'image_data'  => $newPath,
            'accepted_by' => $userId,
        ]);
    }

    /**
     * Delete multiple clippers by ID.
     */
    public function deleteClippersById(array $ids): void
    {
        $clippers = Clipper::whereIn('id', $ids)->get();
        foreach ($clippers as $clipper) {
            $this->deleteClipper($clipper);
        }
    }

    /**
     * Delete a single Clipper and its assets.
     */
    public function deleteClipper(Clipper $clipper): void
    {
        // 1. Clean up pivot table (User collections)
        $clipper->collections()->delete();

        // 2. Clean up physical image 
        // We use getRawOriginal to ensure we get the path 'clippers/xxx.jpg' not the full URL
        $this->imageService->deleteImage($clipper->getRawOriginal('image_data'));

        // 3. Delete DB record
        $clipper->delete();
    }

    /**
     * Batch create helper (for the initial Series creation).
     */
    public function createClippersInBatch(Series $series, array $clippersData, $userId): void
    {
        foreach ($clippersData as $index => $clipperData) {
            if (isset($clipperData['image']) && $clipperData['image'] instanceof UploadedFile) {
                $this->createClipper($series, $clipperData['image'], $index + 1, $userId);
            }
        }
    }

    /**
     * Re-index series numbers.
     */
    protected function reindexSeries(Series $series): void
    {
        $series->clippers()->orderBy('series_number')->get()->each(function ($clipper, $index) {
            $clipper->update(['series_number' => $index + 1]);
        });
    }
}