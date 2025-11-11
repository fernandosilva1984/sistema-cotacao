<?php
// app/Filament/Resources/CotacaoResource/Pages/EditCotacao.php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use League\Csv\Reader;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditCotacao extends EditRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}