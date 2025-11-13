<?php

namespace App\Filament\Resources\ProdutoResource\Pages;

use App\Filament\Resources\ProdutoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\ProdutoImporter;

class ListProdutos extends ListRecords
{
    protected static string $resource = ProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(ProdutoImporter::class)
                ->tooltip('Importar Produtos via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('')
                ->color('success'),
            Actions\CreateAction::make()
                ->tooltip('Criar Novo PRoduto')
                ->label('')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}