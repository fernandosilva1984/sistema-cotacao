<?php
// app/Filament/Concerns/ImportacaoCsv.php

namespace App\Filament\Concerns;

use App\Services\ImportacaoService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ImportacaoCsv
{
    public static function getImportFormSchema(): array
    {
        return [
            Forms\Components\FileUpload::make('arquivo')
                ->label('Arquivo CSV')
                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                ->required()
                ->maxSize(1024)
                ->helperText('Faça o download do template para garantir o formato correto.'),
            
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->action(function ($livewire) {
                        $importacaoService = new ImportacaoService();
                        $template = $importacaoService->getTemplateCsv($livewire->getTipoImportacao());
                        
                        $fileName = "template_{$livewire->getTipoImportacao()}.csv";
                        $filePath = storage_path("app/templates/{$fileName}");
                        
                        if (!is_dir(dirname($filePath))) {
                            mkdir(dirname($filePath), 0755, true);
                        }
                        
                        $handle = fopen($filePath, 'w');
                        foreach ($template as $row) {
                            fputcsv($handle, $row, ';');
                        }
                        fclose($handle);
                        
                        return response()->download($filePath)->deleteFileAfterSend(true);
                    }),
            ])->fullWidth(),
        ];
    }

    public function importarCsv(array $data): void
    {
        $importacaoService = new ImportacaoService();
        $arquivo = $data['arquivo'];
        
        if ($arquivo instanceof TemporaryUploadedFile) {
            $arquivo = new UploadedFile(
                $arquivo->getRealPath(),
                $arquivo->getClientOriginalName(),
                $arquivo->getClientMimeType(),
                $arquivo->getError()
            );
        }
        
        $empresa = auth()->user()->is_master 
            ? (\App\Models\Empresa::find($data['id_empresa'] ?? null) ?? auth()->user()->empresa)
            : auth()->user()->empresa;

        $resultado = $importacaoService->{"importar" . ucfirst($this->getTipoImportacao())}($arquivo, $empresa);

        if ($resultado['success'] > 0) {
            Notification::make()
                ->title('Importação concluída')
                ->body("{$resultado['success']} registros importados com sucesso.")
                ->success()
                ->send();
        }

        if (!empty($resultado['errors'])) {
            foreach ($resultado['errors'] as $error) {
                Notification::make()
                    ->title('Erro na importação')
                    ->body($error)
                    ->danger()
                    ->send();
            }
        }
    }
}