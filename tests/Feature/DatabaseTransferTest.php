<?php

namespace Tests\Feature;

use App\Console\Commands\TransferDatabaseCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_configuration_has_innodb_engine_and_utf8mb4(): void
    {
        $mysqlConfig = config('database.connections.mysql');

        $this->assertSame('InnoDB', $mysqlConfig['engine']);
        $this->assertSame('utf8mb4', $mysqlConfig['charset']);
        $this->assertSame('utf8mb4_unicode_ci', $mysqlConfig['collation']);
        $this->assertTrue($mysqlConfig['strict']);
    }

    public function test_transfer_table_order_respects_foreign_key_hierarchy(): void
    {
        $tables = TransferDatabaseCommand::TABLES;

        $userIndex = array_search('users', $tables, true);
        $categoryIndex = array_search('categories', $tables, true);
        $productIndex = array_search('products', $tables, true);
        $imageIndex = array_search('product_images', $tables, true);
        $paymentIndex = array_search('payments', $tables, true);
        $attemptIndex = array_search('payment_attempts', $tables, true);
        $fulfillmentIndex = array_search('payment_fulfillments', $tables, true);

        // Users & Categories must precede Products
        $this->assertLessThan($productIndex, $userIndex);
        $this->assertLessThan($productIndex, $categoryIndex);

        // Products must precede Product Images & Payments
        $this->assertLessThan($imageIndex, $productIndex);
        $this->assertLessThan($paymentIndex, $productIndex);

        // Payments must precede Attempts & Fulfillments
        $this->assertLessThan($attemptIndex, $paymentIndex);
        $this->assertLessThan($fulfillmentIndex, $paymentIndex);
    }

    public function test_command_rejects_identical_source_and_target(): void
    {
        $this->artisan('db:transfer', [
            '--from' => 'sqlite',
            '--to' => 'sqlite',
        ])
            ->expectsOutput('Koneksi asal (--from) dan koneksi tujuan (--to) tidak boleh sama.')
            ->assertFailed();
    }

    public function test_command_reports_failure_for_unreachable_target(): void
    {
        $this->artisan('db:transfer', [
            '--from' => 'sqlite',
            '--to' => 'invalid_db_connection',
        ])
            ->assertFailed();
    }

    public function test_transfer_copies_records_between_connections_accurately(): void
    {
        // 1. Setup dual in-memory connections for transfer simulation
        config()->set('database.connections.transfer_src', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.connections.transfer_dest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->artisan('migrate', ['--database' => 'transfer_src'])->run();
        $this->artisan('migrate', ['--database' => 'transfer_dest'])->run();

        // 2. Insert sample data on source
        DB::connection('transfer_src')->table('categories')->insert([
            'id' => 1,
            'name' => 'Elektronik Antik',
            'slug' => 'elektronik-antik',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('transfer_src')->table('users')->insert([
            'id' => 1,
            'name' => 'Pramudya',
            'email' => 'pram@example.com',
            'password' => 'secret',
            'role' => 'seller',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Run transfer command
        $this->artisan('db:transfer', [
            '--from' => 'transfer_src',
            '--to' => 'transfer_dest',
            '--truncate' => true,
        ])
            ->expectsOutputToContain('Transfer data antar-database berhasil diselesaikan.')
            ->assertSuccessful();

        // 4. Assert target has identical records
        $destCategory = DB::connection('transfer_dest')->table('categories')->where('id', 1)->first();
        $destUser = DB::connection('transfer_dest')->table('users')->where('id', 1)->first();

        $this->assertNotNull($destCategory);
        $this->assertSame('Elektronik Antik', $destCategory->name);

        $this->assertNotNull($destUser);
        $this->assertSame('Pramudya', $destUser->name);
        $this->assertSame('pram@example.com', $destUser->email);
    }
}
