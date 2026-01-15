<?php

namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Helper to upload image based on environment
     */
    public function uploadImage(UploadedFile $file, string $folder): string
    {
        // store() uses the default disk from .env and returns the relative path automatically.
        return $file->store($folder);
    }

    /**
     * Helper to delete image based on environment
     */
    public function deleteImage(?string $path)
    {
        if (!$path) return;

        // Laravel automatically knows if this path is on Cloudinary or Local based on your .env
        Storage::delete($path);
    }
}