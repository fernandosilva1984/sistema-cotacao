<?php
// app/Services/ImportacaoService.php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Fornecedor;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportacaoService
{
    public function importarUsuarios(UploadedFile $file, Empresa $empresa): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);
        
        $results = [
            'success' => 0,
            'errors' => [],
        ];

        foreach ($csv as $record) {
            try {
                DB::transaction(function () use ($record, $empresa, &$results) {
                    $user = User::create([
                        'name' => $record['nome'],
                        'email' => $record['email'],
                        'password' => bcrypt($record['password'] ?? '123456'),
                        'id_empresa' => $empresa->id,
                        'status' => filter_var($record['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);

                    $results['success']++;
                });
            } catch (\Exception $e) {
                $results['errors'][] = "Erro ao importar usuário {$record['email']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function importarFornecedores(UploadedFile $file, Empresa $empresa): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);
        
        $results = [
            'success' => 0,
            'errors' => [],
        ];

        foreach ($csv as $record) {
            try {
                DB::transaction(function () use ($record, $empresa, &$results) {
                    $fornecedor = Fornecedor::create([
                        'id_empresa' => $empresa->id,
                        'nome' => $record['nome'],
                        'email' => $record['email'],
                        'razao_social' => $record['razao_social'] ?? null,
                        'nome_fantasia' => $record['nome_fantasia'] ?? null,
                        'endereco' => $record['endereco'] ?? null,
                        'status' => filter_var($record['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);

                    $results['success']++;
                });
            } catch (\Exception $e) {
                $results['errors'][] = "Erro ao importar fornecedor {$record['nome']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function importarMarcas(UploadedFile $file, Empresa $empresa): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);
        
        $results = [
            'success' => 0,
            'errors' => [],
        ];

        foreach ($csv as $record) {
            try {
                DB::transaction(function () use ($record, $empresa, &$results) {
                    $marca = Marca::create([
                        'id_empresa' => $empresa->id,
                        'nome' => $record['nome'],
                        'status' => filter_var($record['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);

                    $results['success']++;
                });
            } catch (\Exception $e) {
                $results['errors'][] = "Erro ao importar marca {$record['nome']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function importarProdutos(UploadedFile $file, Empresa $empresa): array
    {
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);
        
        $results = [
            'success' => 0,
            'errors' => [],
        ];

        foreach ($csv as $record) {
            try {
                DB::transaction(function () use ($record, $empresa, &$results) {
                    // Buscar ou criar marca
                    $marca = Marca::firstOrCreate([
                        'id_empresa' => $empresa->id,
                        'nome' => $record['marca'],
                    ], [
                        'status' => true,
                    ]);

                    $produto = Produto::create([
                        'id_empresa' => $empresa->id,
                        'id_marca' => $marca->id,
                        'descricao' => $record['descricao'],
                        'codigo_barras' => $record['codigo_barras'] ?? null,
                        'unidade_medida' => $record['unidade_medida'] ?? 'UN',
                        'observacao' => $record['observacao'] ?? null,
                        'status' => filter_var($record['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);

                    $results['success']++;
                });
            } catch (\Exception $e) {
                $results['errors'][] = "Erro ao importar produto {$record['descricao']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function getTemplateCsv(string $tipo): array
    {
        $templates = [
            'usuarios' => [
                ['nome', 'email', 'password', 'status'],
                ['João Silva', 'joao@empresa.com', '123456', 'true'],
                ['Maria Santos', 'maria@empresa.com', '123456', 'true'],
            ],
            'fornecedores' => [
                ['nome', 'email', 'razao_social', 'nome_fantasia', 'endereco', 'status'],
                ['Fornecedor A', 'contato@fornecedora.com', 'Fornecedor A LTDA', 'Fornecedor A', 'Rua A, 123', 'true'],
                ['Fornecedor B', 'vendas@fornecedorb.com', 'Fornecedor B ME', 'Fornecedor B', 'Av B, 456', 'true'],
            ],
            'marcas' => [
                ['nome', 'status'],
                ['Marca A', 'true'],
                ['Marca B', 'true'],
            ],
            'produtos' => [
                ['descricao', 'marca', 'codigo_barras', 'unidade_medida', 'observacao', 'status'],
                ['Produto Exemplo A', 'Marca A', '1234567890123', 'UN', 'Produto de exemplo', 'true'],
                ['Produto Exemplo B', 'Marca B', '9876543210987', 'KG', 'Produto em kg', 'true'],
            ],
        ];

        return $templates[$tipo] ?? [];
    }
}