<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\UserImporter;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
         Actions\ImportAction::make()
                ->importer(UserImporter::class)
                ->tooltip('Importar Usuários via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('')
                ->color('success'),
            Actions\CreateAction::make()
                ->tooltip('Criar Novo Usuário')
                ->label('')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}