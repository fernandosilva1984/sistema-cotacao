<?php

namespace App\Filament\Resources\ProdutoResource\Pages;

use App\Filament\Resources\ProdutoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Imports\ProdutoImporter;

class ManageProduto extends ManageRecords
{
    protected static string $resource = ProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Adicionar Produto')
                ->icon('heroicon-o-plus'),
            Actions\ImportAction::make()
                ->importer(ProdutoImporter::class)
                ->tooltip('Importar Produtos via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('Importar Produtos')
                ->color('success'),
        ];
    }
}
