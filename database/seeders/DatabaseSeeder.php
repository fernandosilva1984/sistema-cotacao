<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Criar empresa master
        $empresa = Empresa::create([
            'razao_social' => 'Sistema de Cotações LTDA',
            'nome_fantasia' => 'Sistema Cotações',
            'endereco' => 'Rua Exemplo, 123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'cnpj' => '12.345.678/0001-90',
            'contato' => '(11) 99999-9999',
            'email' => 'admin@sistemacotacoes.com',
            'status' => true,
        ]);

        // Criar usuário master
        User::create([
            'name' => 'Administrador Master',
            'email' => 'admin@sistemacotacoes.com',
            'password' => Hash::make('123456'),
            'id_empresa' => $empresa->id,
            'status' => true,
            'is_master' => true,
        ]);

        // Criar empresa de exemplo
        $empresaExemplo = Empresa::create([
            'razao_social' => 'Empresa Exemplo LTDA',
            'nome_fantasia' => 'Empresa Exemplo',
            'endereco' => 'Av. Principal, 456',
            'bairro' => 'Jardins',
            'cidade' => 'Rio de Janeiro',
            'cnpj' => '98.765.432/0001-10',
            'contato' => '(21) 88888-8888',
            'email' => 'contato@empresaexemplo.com',
            'status' => true,
        ]);

        // Criar usuário para empresa exemplo
        User::create([
            'name' => 'Gerente Empresa Exemplo',
            'email' => 'gerente@empresaexemplo.com',
            'password' => Hash::make('123456'),
            'id_empresa' => $empresaExemplo->id,
            'status' => true,
            'is_master' => false,
        ]);
    }
}