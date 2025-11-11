<?php
// app/Filament/Resources/CotacaoResource/Pages/CreateCotacao.php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Resources\Pages\CreateRecord;
use League\Csv\Reader;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateCotacao extends CreateRecord
{
    protected static string $resource = CotacaoResource::class;


}