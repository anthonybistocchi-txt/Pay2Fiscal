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
            ['email' => 'anthony@abcode.com.br'],
            [
                'name' => 'Anthony',
                'password' => Hash::make('12345678'),
                'cpf' => '12345678901',
                'cnpj' => null,
                'street' => 'Av. Presidente Vargas',
                'number' => '1000',
                'complement' => 'Sala 101',
                'neighborhood' => 'Jardim Sumaré',
                'city' => 'Ribeirão Preto',
                'state' => 'SP',
                'zip_code' => '14020010',
                'country' => 'BR',
            ],
        );
    }
}

