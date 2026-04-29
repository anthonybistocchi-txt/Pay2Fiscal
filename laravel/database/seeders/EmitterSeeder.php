<?php

namespace Database\Seeders;

use App\Models\Emitter;
use Illuminate\Database\Seeder;

class EmitterSeeder extends Seeder
{
    public function run(): void
    {
        // Single-company setup: keep exactly one active emitter record.
        Emitter::query()->updateOrCreate(
            ['cnpj' => '12345678000195'],
            [
                'legal_name' => 'ABCode Desenvolvimento de Software LTDA',
                'trade_name' => 'ABCode',
                'ie' => '123456789012',
                'im' => null,
                'tax_regime' => 'SIMPLES',
                'crt' => '1',
                'street' => 'Av. Presidente Vargas',
                'number' => '1000',
                'complement' => 'Sala 101',
                'neighborhood' => 'Jardim Sumaré',
                'city' => 'Ribeirão Preto',
                'state' => 'SP',
                'zip_code' => '14020010',
                'country' => 'BR',
                'email' => 'financeiro@abcode.com.br',
                'phone' => '16999999999',
            ],
        );
    }
}

