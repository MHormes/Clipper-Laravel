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
        $driver = DB::getDriverName();

        // 1. Handle Constraints based on Database Driver
        if ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        DB::beginTransaction();

        $tables = [
            'users' => 'users.csv',
            'series' => 'series.csv',
            'clippers' => 'clippers.csv',
            'collected_clippers' => 'collected_clippers.csv',
            'user_follows' => 'user_follows.csv',
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

        // Re-enable for SQLite (Postgres transaction commit handles it automatically)
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

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
            if ($chunk->isEmpty()) {
                return; 
            }

            $preparedData = $chunk->map(function ($item) {
                // Convert values for better database integrity
                return array_map(function($value) {
                    if ($value === '' || $value === null) return null;
                    if ($value === 'true') return true;
                    if ($value === 'false') return false;
                    return $value;
                }, $item);
            })->toArray();

            if (empty($preparedData)) {
                return;
            }

            // Get columns for the upsert update list
            $columns = array_keys(reset($preparedData));

            // SQLite upsert requires a unique index to work. 
            // 'id' is perfect since it's our primary key.
            DB::table($table)->upsert(
                $preparedData, 
                ['id'],          
                $columns         
            );
        });
    }
}