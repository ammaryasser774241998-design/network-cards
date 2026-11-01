<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ImportRenderDatabase extends Command
{
    protected $signature = 'database:import-render
                            {--run : Copy data after reviewing the dry-run counts}';

    protected $description = 'Safely copy application data from local MySQL to an empty external PostgreSQL database';

    private const TABLES = [
        'tenants',
        'users',
        'card_categories',
        'customers',
        'system_settings',
        'tenant_settings',
        'vouchers',
        'bank_transactions',
        'card_sales',
        'customer_ledger_entries',
        'phone_exclusions',
        'work_shifts',
        'sms_outbox',
    ];

    public function handle(): int
    {
        $source = DB::connection();
        if ($source->getDriverName() !== 'mysql') {
            $this->components->error('The configured source database must be MySQL.');

            return self::FAILURE;
        }

        $databaseUrl = $this->secret('External PostgreSQL connection URL');
        if (! is_string($databaseUrl) || ! preg_match('/^postgres(?:ql)?:\/\//i', $databaseUrl)) {
            $this->components->error('A valid PostgreSQL connection URL is required.');

            return self::FAILURE;
        }

        if (! preg_match('/(?:[?&])sslmode=/i', $databaseUrl)) {
            $databaseUrl .= (str_contains($databaseUrl, '?') ? '&' : '?').'sslmode=require';
        }

        config([
            'database.connections.render_import' => array_replace(
                config('database.connections.pgsql'),
                ['url' => $databaseUrl],
            ),
        ]);
        DB::purge('render_import');
        $target = DB::connection('render_import');

        if ($target->getDriverName() !== 'pgsql') {
            $this->components->error('The destination database connection is not PostgreSQL.');

            return self::FAILURE;
        }

        if (! $target->table('migrations')
            ->where('migration', '2026_11_01_080000_remove_legacy_customer_messaging')
            ->exists()) {
            $this->components->error('Deploy the application and run its migrations before importing data.');

            return self::FAILURE;
        }

        try {
            $tables = $this->validateTables($source, $target);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = $source->table($table)->count();
        }

        $this->table(['Table', 'Rows to copy'], array_map(
            static fn (string $table, int $count): array => [$table, $count],
            array_keys($counts),
            array_values($counts),
        ));

        if (! $this->option('run')) {
            $this->components->info('Dry run only. Review the counts, then repeat with --run to copy the data.');

            return self::SUCCESS;
        }

        if (! $this->confirm(
            'This copies user accounts, password hashes, customer records, card PINs, and financial records to the external provider. Continue?'
        )) {
            return self::SUCCESS;
        }

        try {
            $target->transaction(function () use ($source, $target, $tables): void {
                foreach ($tables as $table) {
                    $columns = $this->matchingColumns($source, $target, $table);
                    $primaryKey = $table === 'system_settings' ? 'key' : 'id';
                    $copied = 0;

                    $source->table($table)
                        ->orderBy($primaryKey)
                        ->chunk(250, function ($rows) use (
                            $target,
                            $table,
                            $columns,
                            &$copied,
                        ): void {
                            $batch = [];
                            foreach ($rows as $row) {
                                $record = (array) $row;
                                $batch[] = array_intersect_key($record, array_flip($columns));
                            }

                            if ($batch !== []) {
                                $target->table($table)->insert($batch);
                                $copied += count($batch);
                            }
                        });

                    $this->line("Copied {$copied} rows from {$table}.");
                }

                foreach ($tables as $table) {
                    if ($table === 'system_settings') {
                        continue;
                    }

                    $sequence = $target->selectOne(
                        'SELECT pg_get_serial_sequence(?, ?) AS sequence_name',
                        [$table, 'id'],
                    )->sequence_name;

                    if ($sequence !== null) {
                        $target->statement(
                            'SELECT setval(?, COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM "'.$table.'"',
                            [$sequence],
                        );
                    }
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Import failed and the destination transaction was rolled back. Check the application log.');

            return self::FAILURE;
        }

        $this->components->info('Database import completed. Existing browser sessions and API tokens were intentionally not copied.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function validateTables(Connection $source, Connection $target): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            if (! Schema::connection($source->getName())->hasTable($table)
                || ! Schema::connection($target->getName())->hasTable($table)) {
                throw new RuntimeException("Required application table is missing: {$table}");
            }

            if ($target->table($table)->exists()) {
                throw new RuntimeException(
                    "Destination table {$table} is not empty. Import only into a newly migrated database."
                );
            }

            $this->matchingColumns($source, $target, $table);
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * @return list<string>
     */
    private function matchingColumns(Connection $source, Connection $target, string $table): array
    {
        $sourceColumns = Schema::connection($source->getName())->getColumnListing($table);
        $targetColumns = Schema::connection($target->getName())->getColumnListing($table);
        $sourceOnly = array_values(array_diff($sourceColumns, $targetColumns));
        $targetOnly = array_values(array_diff($targetColumns, $sourceColumns));

        if ($sourceOnly !== [] || $targetOnly !== []) {
            throw new RuntimeException("The source and destination schemas differ for table {$table}.");
        }

        return $sourceColumns;
    }
}
