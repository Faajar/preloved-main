<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TransferDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:transfer 
                            {--from=sqlite : Nama koneksi database asal}
                            {--to=mysql : Nama koneksi database tujuan}
                            {--truncate : Kosongkan tabel tujuan sebelum melakukan transfer data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer seluruh data tabel domain dari satu database (misal SQLite) ke database lain (misal MySQL) dengan integritas foreign key';

    /**
     * Urutan tabel yang ketat sesuai dependensi foreign key.
     *
     * @var list<string>
     */
    public const TABLES = [
        'users',
        'password_reset_tokens',
        'categories',
        'products',
        'product_images',
        'payments',
        'boosts',
        'reports',
        'payment_attempts',
        'payment_fulfillments',
        'payment_refunds',
        'payment_webhook_events',
        'moderation_audits',
        'settings',
    ];

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $shouldTruncate = (bool) $this->option('truncate');

        if ($from === $to) {
            $this->error('Koneksi asal (--from) dan koneksi tujuan (--to) tidak boleh sama.');

            return self::FAILURE;
        }

        $this->info("Menyiapkan transfer data dari [{$from}] ke [{$to}]...");

        try {
            DB::connection($from)->getPdo();
        } catch (Throwable $e) {
            $this->error("Koneksi asal [{$from}] gagal: ".$e->getMessage());

            return self::FAILURE;
        }

        try {
            DB::connection($to)->getPdo();
        } catch (Throwable $e) {
            $this->error("Koneksi tujuan [{$to}] gagal: ".$e->getMessage());

            return self::FAILURE;
        }

        // Verifikasi apakah tabel sudah dimigrasikan di database tujuan
        $missingTables = [];
        foreach (self::TABLES as $table) {
            if (! Schema::connection($to)->hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if ($missingTables !== []) {
            $this->error('Tabel tujuan belum siap. Jalankan migrasi terlebih dahulu di database tujuan:');
            $this->line("php artisan migrate --database={$to}");
            $this->line('Tabel yang belum ditemukan: '.implode(', ', $missingTables));

            return self::FAILURE;
        }

        $this->disableForeignKeys($to);

        try {
            if ($shouldTruncate) {
                $this->info('Mengosongkan tabel tujuan...');
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::connection($to)->table($table)->truncate();
                }
            }

            $summary = [];

            foreach (self::TABLES as $table) {
                if (! Schema::connection($from)->hasTable($table)) {
                    continue;
                }

                $sourceCount = DB::connection($from)->table($table)->count();
                $transferred = 0;

                if ($sourceCount > 0) {
                    $rows = DB::connection($from)->table($table)->get();
                    $insertData = [];

                    foreach ($rows as $row) {
                        $insertData[] = (array) $row;
                    }

                    foreach (array_chunk($insertData, 200) as $chunk) {
                        DB::connection($to)->table($table)->insert($chunk);
                        $transferred += count($chunk);
                    }
                }

                $targetCount = DB::connection($to)->table($table)->count();

                // Reset auto increment jika target mendukung (MySQL)
                $this->resetAutoIncrement($to, $table);

                $summary[] = [
                    'table' => $table,
                    'source' => $sourceCount,
                    'target' => $targetCount,
                    'status' => ($sourceCount === $targetCount) ? 'OK' : 'MISMATCH',
                ];
            }

            $this->table(['Tabel', 'Baris Asal', 'Baris Tujuan', 'Status'], $summary);
            $this->info('Transfer data antar-database berhasil diselesaikan.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Terjadi kesalahan saat transfer: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $this->enableForeignKeys($to);
        }
    }

    private function disableForeignKeys(string $connection): void
    {
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'mysql') {
            DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::connection($connection)->statement('PRAGMA foreign_keys = OFF;');
        }
    }

    private function enableForeignKeys(string $connection): void
    {
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'mysql') {
            DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::connection($connection)->statement('PRAGMA foreign_keys = ON;');
        }
    }

    private function resetAutoIncrement(string $connection, string $table): void
    {
        $driver = DB::connection($connection)->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        if (! Schema::connection($connection)->hasColumn($table, 'id')) {
            return;
        }

        $maxId = (int) DB::connection($connection)->table($table)->max('id');
        $nextId = $maxId + 1;

        DB::connection($connection)->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId};");
    }
}
