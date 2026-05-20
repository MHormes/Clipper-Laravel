<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class SeedStorage extends Command
{
    protected $signature = 'storage:seed {--endpoint= : Override the S3 endpoint (internal container URL)}';
    protected $description = 'Seed AIStor/S3 bucket with local storage files if bucket is empty';

    public function handle(): int
    {
        $s3 = $this->buildDisk();

        $this->ensureBucketExists($s3);

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
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $s3->put($relativePath, file_get_contents($file->getRealPath()));
            $count++;

            if ($count % 50 === 0) {
                $this->info("  Uploaded {$count} files...");
            }
        }

        $this->info("Storage seeded: {$count} files uploaded.");
        return self::SUCCESS;
    }

    private function ensureBucketExists($disk): void
    {
        $endpoint = $this->option('endpoint') ?: config('filesystems.disks.s3.endpoint');
        $bucket   = config('filesystems.disks.s3.bucket');

        $client = new S3Client([
            'version'                 => 'latest',
            'region'                  => config('filesystems.disks.s3.region') ?: 'us-east-1',
            'endpoint'                => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        if (!$client->doesBucketExist($bucket)) {
            $this->info("Bucket '{$bucket}' not found. Creating...");
            $client->createBucket(['Bucket' => $bucket]);
            $this->info("Bucket '{$bucket}' created.");
        }
    }

    private function buildDisk()
    {
        $endpoint = $this->option('endpoint');

        if (!$endpoint) {
            return Storage::disk('s3');
        }

        return Storage::build([
            'driver'                  => 's3',
            'key'                     => config('filesystems.disks.s3.key'),
            'secret'                  => config('filesystems.disks.s3.secret'),
            'region'                  => config('filesystems.disks.s3.region'),
            'bucket'                  => config('filesystems.disks.s3.bucket'),
            'endpoint'                => $endpoint,
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint'),
        ]);
    }
}
