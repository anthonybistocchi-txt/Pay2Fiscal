<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        Gateway::query()->updateOrCreate(
            ['name' => 'Mock Gateway (NestJS)'],
            [
                'description'   => 'Gateway simulado do serviço Node para testes locais via Docker.',
                'base_url'      => 'http://node:3000',
                'dispatch_path' => 'payments/dispatch',
                'priority'      => 1,
                'active'        => true,
            ],
        );
    }
}
