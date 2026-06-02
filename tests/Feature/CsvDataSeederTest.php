<?php

use App\Models\Clipper;
use App\Models\Series;
use App\Models\User;
use Database\Seeders\CsvDataSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->csvDataPath = storage_path('framework/testing/csv-seeder-'.Str::uuid());

    File::ensureDirectoryExists($this->csvDataPath);

    config()->set('database.seed_data_path', $this->csvDataPath);
});

afterEach(function () {
    File::deleteDirectory($this->csvDataPath);
});

test('csv seeder imports postgres boolean literals correctly', function () {
    $adminId = '019bb7be-fec4-7390-a7e1-63b1a0c1067f';
    $regularSeriesId = '019bb7c0-7cea-7033-933a-52542b7e8731';
    $customSeriesId = '019bb7c6-fd07-7034-b242-ac1ca21d0af0';
    $regularClipperId = '019bb7c0-8031-7128-83e1-88a7100313c0';
    $autoAddClipperId = '019bb7c0-83d7-7352-b94b-d8e8424da48e';
    $inactiveUserId = '019bb7be-fec4-7390-a7e1-63b1a0c10680';

    writeCsvSeederFixture($this->csvDataPath.'/users.csv', [
        ['id', 'name', 'email', 'password', 'role', 'is_active', 'created_at', 'updated_at'],
        [$adminId, 'Admin', 'admin@example.test', bcrypt('password'), 'admin', 't', '2026-01-13 14:26:44', '2026-01-13 14:26:44'],
        [$inactiveUserId, 'Inactive User', 'inactive@example.test', bcrypt('password'), 'user', 'f', '2026-01-13 14:26:44', '2026-01-13 14:26:44'],
    ]);

    writeCsvSeederFixture($this->csvDataPath.'/series.csv', [
        ['id', 'name', 'custom', 'accepted_by', 'image_data', 'created_at', 'updated_at', 'requested_by'],
        [$regularSeriesId, 'Regular Series', 'f', $adminId, null, '2026-01-13 14:26:44', '2026-01-13 14:26:44', $adminId],
        [$customSeriesId, 'Custom Series', 't', $adminId, null, '2026-01-13 14:26:44', '2026-01-13 14:26:44', $adminId],
    ]);

    writeCsvSeederFixture($this->csvDataPath.'/clippers.csv', [
        ['id', 'series_id', 'series_number', 'accepted_by', 'image_data', 'created_at', 'updated_at', 'requested_by', 'auto_add_to_collection'],
        [$regularClipperId, $regularSeriesId, 1, $adminId, null, '2026-01-13 14:26:44', '2026-01-13 14:26:44', $adminId, 'f'],
        [$autoAddClipperId, $customSeriesId, 1, $adminId, null, '2026-01-13 14:26:44', '2026-01-13 14:26:44', $adminId, 't'],
    ]);

    $this->seed(CsvDataSeeder::class);

    expect(Series::findOrFail($regularSeriesId)->custom)->toBeFalse()
        ->and(Series::findOrFail($customSeriesId)->custom)->toBeTrue()
        ->and(Clipper::findOrFail($regularClipperId)->auto_add_to_collection)->toBeFalse()
        ->and(Clipper::findOrFail($autoAddClipperId)->auto_add_to_collection)->toBeTrue()
        ->and(User::findOrFail($inactiveUserId)->is_active)->toBeFalse();
});

function writeCsvSeederFixture(string $path, array $rows): void
{
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);
}
