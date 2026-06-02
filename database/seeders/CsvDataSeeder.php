<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;

class CsvDataSeeder extends Seeder
{
    private const BOOLEAN_COLUMNS = [
        'clippers' => ['auto_add_to_collection'],
        'series' => ['custom'],
        'users' => ['is_active'],
    ];

    public function run(): void
    {
        $driver = DB::getDriverName();

        // Handle constraints based on database driver.
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
            $path = "{$this->dataDirectory()}/{$fileName}";

            if (!File::exists($path)) {
                $this->command->warn("Skipping {$table}: File not found at {$path}");
                continue;
            }

            $this->importTable($table, $path);
        }

        DB::commit();

        // Re-enable for SQLite. Postgres transaction commit handles it automatically.
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
                yield array_combine($header, $row);
            }
            fclose($handle);
        })
        ->chunk(250)
        ->each(function ($chunk) use ($table) {
            if ($chunk->isEmpty()) {
                return; 
            }

            $preparedData = $chunk->map(function ($item) use ($table) {
                return $this->prepareRow($table, $item);
            })->toArray();

            if (empty($preparedData)) {
                return;
            }

            $columns = array_keys(reset($preparedData));

            // SQLite upsert requires a unique index. The UUID id is the shared key.
            DB::table($table)->upsert(
                $preparedData, 
                ['id'],          
                $columns         
            );
        });
    }

    private function dataDirectory(): string
    {
        return config('database.seed_data_path', database_path('data'));
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function prepareRow(string $table, array $row): array
    {
        foreach ($row as $column => $value) {
            $row[$column] = $this->prepareValue($table, $column, $value);
        }

        return $row;
    }

    private function prepareValue(string $table, string $column, mixed $value): mixed
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if (!$this->isBooleanColumn($table, $column)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            't', 'true', '1' => true,
            'f', 'false', '0' => false,
            default => $value,
        };
    }

    private function isBooleanColumn(string $table, string $column): bool
    {
        return in_array($column, self::BOOLEAN_COLUMNS[$table] ?? [], true);
    }
}
