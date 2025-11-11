<?php
// app/Filament/Imports/FornecedorImporter.php

namespace App\Filament\Imports;

use App\Models\Fornecedor;
use App\Models\Empresa;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class FornecedorImporter extends Importer
{
    protected static ?string $model = Fornecedor::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nome')
                ->label('Nome')
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                ->label('Email')
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('razao_social')
                ->label('Razão Social')
                ->rules(['max:255']),
            ImportColumn::make('nome_fantasia')
                ->label('Nome Fantasia')
                ->rules(['max:255']),
            ImportColumn::make('endereco')
                ->label('Endereço')
                ->rules(['max:255']),
            ImportColumn::make('id_empresa')
                ->label('ID Empresa')
                ->numeric()
                ->rules(['required', 'exists:empresas,id']),
            ImportColumn::make('status')
                ->label('Status')
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public function resolveRecord(): ?Fornecedor
    {
        $fornecedor = Fornecedor::where('email', strtolower($this->data['email']))
            ->where('id_empresa', $this->data['id_empresa'])
            ->first();
        
        return $fornecedor ?? new Fornecedor();
    }

    public function beforeSave(): void
    {
        $this->record->fill([
            'nome' => $this->data['nome'],
            'email' => strtolower($this->data['email']),
            'razao_social' => $this->data['razao_social'] ?? null,
            'nome_fantasia' => $this->data['nome_fantasia'] ?? null,
            'endereco' => $this->data['endereco'] ?? null,
            'id_empresa' => (int) $this->data['id_empresa'],
            'status' => filter_var($this->data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importação de fornecedores concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam.';
        }

        return $body;
    }
}