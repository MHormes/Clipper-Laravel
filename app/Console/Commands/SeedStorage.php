<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class SeedStorage extends Command
{
    protected $signature = 'storage:seed';
    protected $description = 'Seed AIStor/S3 bucket with local storage files if bucket is empty';

    public function handle(): int
    {
        $s3 = Storage::disk('s3');

        $existing = $s3->allFiles();
        if (count($existing) > 0) {
            $this->info('Storage already seeded (' . count($existing) . ' files). Skipping.');
            return self::SUCCESS;
        }

        $this->info('Storage empty. Seeding from storage/app/public...');

        $sourcePath = storage_path('app/public');
        $finder = Finder::create()->files()->in($sourcePath)->notName('.gitignore');

        $count = 0;
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            // Normalize Windows backslashes
            $relativePath = str_replace('\\', '/', $relativePath);

            $s3->put($relativePath, file_get_contents($file->getRealPath()));
            $count++;

            if ($count % 50 === 0) {
                $this->info("  Uploaded {$count} files...");
            }
        }

        $this->info("Storage seeded: {$count} files uploaded.");
        return self::SUCCESS;
    }
}
