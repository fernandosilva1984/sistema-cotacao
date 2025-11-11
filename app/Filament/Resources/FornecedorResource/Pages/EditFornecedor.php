<?php

namespace App\Filament\Resources\FornecedorResource\Pages;

use App\Filament\Resources\FornecedorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFornecedor extends EditRecord
{
    protected static string $resource = FornecedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
