<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductFiscal;
use Illuminate\Database\Seeder;

/**
 * Produtos com NCMs reservados para simulação fiscal no NestJS (SIMULATION_NCM).
 * Use um produto por cenário ao testar POST /purchase + queue:work.
 */
class FiscalSimulationProductSeeder extends Seeder
{
    private const FISCAL_DEFAULTS = [
        'origin_product' => 0,
        'cest' => null,
        'cfop' => '5102',
        'icms_cst_csosn' => '102',
        'pis_cst' => '49',
        'cofins_cst' => '49',
    ];

    public function run(): void
    {
        $products = [
            [
                'name' => '[SIM] NF-e em processamento',
                'description' => 'Simula resposta PROCESSING (HTTP 202) no serviço fiscal.',
                'price' => 1000,
                'fiscal' => array_merge(self::FISCAL_DEFAULTS, ['ncm' => '99999997']),
            ],
            [
                'name' => '[SIM] NF-e rejeitada',
                'description' => 'Simula resposta REJECTED (HTTP 422) no serviço fiscal.',
                'price' => 1000,
                'fiscal' => array_merge(self::FISCAL_DEFAULTS, ['ncm' => '99999998']),
            ],
            [
                'name' => '[SIM] NF-e denegada',
                'description' => 'Simula resposta DENIED (HTTP 422) no serviço fiscal.',
                'price' => 1000,
                'fiscal' => array_merge(self::FISCAL_DEFAULTS, ['ncm' => '99999999']),
            ],
            [
                'name' => '[SIM] NF-e erro interno',
                'description' => 'Simula resposta ERROR (HTTP 500) no serviço fiscal.',
                'price' => 1000,
                'fiscal' => array_merge(self::FISCAL_DEFAULTS, ['ncm' => '99999996']),
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
