<?php

namespace App\Filament\Resources\FornecedorResource\Pages;

use App\Filament\Resources\FornecedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\FornecedorImporter;

class ListFornecedores extends ListRecords
{
    protected static string $resource = FornecedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
             Actions\ImportAction::make()
                ->importer(FornecedorImporter::class)
                ->tooltip('Importar Fornecedores via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('')
                ->color('success'),
            Actions\CreateAction::make()
                ->tooltip('Criar Novo Fornecedor')
                ->label('')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}