<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;

class CsvDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Handle Postgres Constraints
        // This allows us to insert data even if the order isn't perfect, 
        // checking constraints only at the end of the transaction.
        DB::beginTransaction();
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $tables = [
            'users' => 'users.csv',
            'series' => 'series.csv',
            'clippers' => 'clippers.csv',
            'collected_clippers' => 'collected_clippers.csv',
        ];

        foreach ($tables as $table => $fileName) {
            $path = database_path("data/{$fileName}");

            if (!File::exists($path)) {
                $this->command->warn("Skipping {$table}: File not found at {$path}");
                continue;
            }

            $this->importTable($table, $path);
        }

        DB::commit();
        $this->command->info('Full data import completed successfully.');
    }

    private function importTable(string $table, string $path): void
    {
        $this->command->info("Importing: {$table}...");

        LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                // Combine header with row values
                yield array_combine($header, $row);
            }
            fclose($handle);
        })
        ->chunk(250)
        ->each(function ($chunk) use ($table) {
            $preparedData = $chunk->map(function ($item) {
                // Convert empty CSV strings to actual NULLs for Postgres
                return array_map(fn($value) => $value === '' ? null : $value, $item);
            })->toArray();

            DB::table($table)->insertOrIgnore($preparedData);
        });
    }
}