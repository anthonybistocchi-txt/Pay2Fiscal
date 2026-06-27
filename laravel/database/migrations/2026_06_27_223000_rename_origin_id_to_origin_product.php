<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['product_fiscal', 'transaction_fiscal_data'] as $table) {
            if (! Schema::hasColumn($table, 'origin_id')) {
                continue;
            }

            if (Schema::hasColumn($table, 'origin_product')) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} RENAME COLUMN origin_id TO origin_product");
        }
    }

    public function down(): void
    {
        foreach (['product_fiscal', 'transaction_fiscal_data'] as $table) {
            if (! Schema::hasColumn($table, 'origin_product')) {
                continue;
            }

            if (Schema::hasColumn($table, 'origin_id')) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} RENAME COLUMN origin_product TO origin_id");
        }
    }
};
