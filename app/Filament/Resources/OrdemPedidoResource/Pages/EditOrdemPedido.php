<?php

namespace App\Filament\Resources\OrdemPedidoResource\Pages;

use App\Filament\Resources\OrdemPedidoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdemPedido extends EditRecord
{
    protected static string $resource = OrdemPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
