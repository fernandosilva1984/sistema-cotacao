<?php

namespace App\Filament\Resources\OrdemPedidoResource\Pages;

use App\Filament\Resources\OrdemPedidoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdemPedidos extends ListRecords
{
    protected static string $resource = OrdemPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->tooltip('Criar Nova Ordem de Pedido')
                ->label('')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}