<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

#[Signature('backup:database')]
#[Description('Write a SQL backup of the application database')]
class BackupDatabase extends Command
{
    /**
     * @var list<string>
     */
    private const array SkippedTables = [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
    ];

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/database-'.now()->format('Ymd-His').'.sql';
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            $this->error('The backup file could not be created.');

            return self::FAILURE;
        }

        $driver = DB::getDriverName();
        fwrite($handle, '-- Jumuika Pass database backup '.now()->toIso8601String().PHP_EOL);
        fwrite($handle, '-- Restore onto an empty database that has already been migrated.'.PHP_EOL);

        if ($driver === 'mysql') {
            fwrite($handle, 'SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL);
        }

        foreach ($this->tables() as $table) {
            $this->writeTable($handle, $table);
        }

        if ($driver === 'mysql') {
            fwrite($handle, 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL);
        }

        fclose($handle);
        $this->prune($directory);
        $this->info($path);

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        $names = [];

        foreach (Schema::getTables() as $table) {
            $name = is_array($table) ? (string) ($table['name'] ?? '') : (string) $table;

            if ($name === '' || str_starts_with($name, 'sqlite_') || in_array($name, self::SkippedTables, true)) {
                continue;
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param  resource  $handle
     */
    private function writeTable($handle, string $table): void
    {
        $columns = array_map(
            fn (array $column): string => (string) $column['name'],
            Schema::getColumns($table),
        );

        if ($columns === []) {
            return;
        }

        $grammar = DB::getQueryGrammar();
        $wrappedTable = $grammar->wrapTable($table);
        $wrappedColumns = implode(', ', array_map(fn (string $column): string => $grammar->wrap($column), $columns));
        $query = DB::table($table)->select($columns);

        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        }

        foreach ($query->cursor() as $row) {
            $values = array_map(
                fn (string $column): string => $this->sqlValue($row->{$column} ?? null),
                $columns,
            );

            fwrite($handle, "INSERT INTO {$wrappedTable} ({$wrappedColumns}) VALUES (".implode(', ', $values).');'.PHP_EOL);
        }
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        return (string) DB::getPdo()->quote((string) $value);
    }

    private function prune(string $directory): void
    {
        $cutoff = now()->subDays(14)->getTimestamp();

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() === 'sql' && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
