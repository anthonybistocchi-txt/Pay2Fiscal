<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()->each(function (Product $product): void {
            Stock::query()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 100],
            );
        });
    }
}
