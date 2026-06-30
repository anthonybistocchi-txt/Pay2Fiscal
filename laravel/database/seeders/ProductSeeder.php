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
                'name' => 'Café torrado e moído 500g',
                'description' => 'Café torrado e moído, embalagem 500g. Use para simular EMITTED (NCM real).',
                'price' => 1890,
                'fiscal' => [
                    // 0 = Nacional
                    'origin_product' => 0,
                    // NCM 0901.21.00 (café torrado, não descafeinado)
                    'ncm' => '09012100',
                    'cest' => null,
                    'cfop' => '5102',
                    'icms_cst_csosn' => '102',
                    'pis_cst' => '49',
                    'cofins_cst' => '49',
                ],
            ],
            [
                'name' => 'Caderno universitário 200 folhas',
                'description' => 'Caderno universitário espiral 200 folhas.',
                'price' => 2590,
                'fiscal' => [
                    // 0 = Nacional
                    'origin_product' => 0,
                    // NCM 4820.20.00 (cadernos)
                    'ncm' => '48202000',
                    'cest' => null,
                    'cfop' => '5102',
                    'icms_cst_csosn' => '102',
                    'pis_cst' => '49',
                    'cofins_cst' => '49',
                ],
            ],
            [
                'name' => 'Detergente líquido 500ml',
                'description' => 'Detergente líquido para limpeza, frasco 500ml.',
                'price' => 690,
                'fiscal' => [
                    // 0 = Nacional
                    'origin_product' => 0,
                    // NCM 3402.20.00 (preparações para limpeza - detergentes)
                    'ncm' => '34022000',
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

