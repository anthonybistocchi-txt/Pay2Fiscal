<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductFiscal;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Assinatura Mensal',
                'description' => 'Acesso mensal ao serviço.',
                'price' => 1990,
                'fiscal' => [
                    'origin_id' => 0,
                    'ncm' => '99999999',
                    'cest' => null,
                    'cfop' => '5102',
                    'icms_cst_csosn' => '102',
                    'pis_cst' => '49',
                    'cofins_cst' => '49',
                ],
            ],
            [
                'name' => 'Setup Inicial',
                'description' => 'Configuração inicial do ambiente.',
                'price' => 9900,
                'fiscal' => [
                    'origin_id' => 0,
                    'ncm' => '99999999',
                    'cest' => null,
                    'cfop' => '5102',
                    'icms_cst_csosn' => '102',
                    'pis_cst' => '49',
                    'cofins_cst' => '49',
                ],
            ],
        ];

        foreach ($products as $payload) {
            $product = Product::query()->updateOrCreate(
                ['name' => $payload['name']],
                [
                    'description' => $payload['description'],
                    'price' => $payload['price'],
                ],
            );

            /** @var array<string, mixed> $fiscal */
            $fiscal = $payload['fiscal'];

            ProductFiscal::query()->updateOrCreate(
                ['product_id' => $product->id],
                $fiscal,
            );
        }
    }
}

