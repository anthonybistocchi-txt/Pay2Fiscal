<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'cpf' => '12345678901',
                'cnpj' => null,
                'street' => 'Rua Itatiaia',
                'number' => '123',
                'complement' => null,
                'neighborhood' => 'Centro',
                'city' => 'Ribeirão Preto',
                'state' => 'SP',
                'zip_code' => '14010000',
                'country' => 'BR',
            ],
        );
    }
}

