<?php

namespace App\Filament\Resources\MarcaResource\Pages;

use App\Filament\Resources\MarcaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\MarcaImporter;

class ListMarcas extends ListRecords
{
    protected static string $resource = MarcaResource::class;

    protected function getHeaderActions(): array
    {
        return [
             Actions\ImportAction::make()
                ->importer(MarcaImporter::class)
                ->tooltip('Importar Marcas via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('')
                ->color('success'),
            Actions\CreateAction::make()
                ->tooltip('Criar Nova Marca')
                ->label('')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}