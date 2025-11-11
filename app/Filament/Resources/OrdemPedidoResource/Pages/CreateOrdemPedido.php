<?php
// app/Filament/Resources/OrdemPedidoResource/Pages/CreateOrdemPedido.php

namespace App\Filament\Resources\OrdemPedidoResource\Pages;

use App\Filament\Resources\OrdemPedidoResource;
use App\Models\OrdemPedido;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrdemPedido extends CreateRecord
{
    protected static string $resource = OrdemPedidoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Usar o método personalizado para criar múltiplas ordens
        OrdemPedidoResource::createMultipleOrders($data);
        
        // Retornar um modelo "fictício" apenas para compatibilidade
        // Como estamos criando múltiplas ordens, não faz sentido retornar uma específica
        return new OrdemPedido();
    }

    protected function getRedirectUrl(): string
    {
        // Redirecionar para a lista de ordens após criar múltiplas
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $data = $this->form->getState();
        $totalOrdens = count($data['ordens_por_fornecedor'] ?? []);
        
        return $totalOrdens > 1 
            ? "{$totalOrdens} ordens de pedido criadas com sucesso!"
            : "Ordem de pedido criada com sucesso!";
    }
}