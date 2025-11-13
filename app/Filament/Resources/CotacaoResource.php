<?php
// app/Filament/Resources/CotacaoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\CotacaoResource\Pages;
use App\Models\Cotacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use App\Services\EmailService;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;
    protected static ?string $slug = 'cotacoes';
    protected static ?string $navigationLabel = 'Cotações';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Operacional';
    protected static ?string $pluralModelLabel = 'Cotações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // ---------------------- SEÇÃO 1 ----------------------
                Forms\Components\Section::make('Informações da Cotação')
                    ->schema([
                        Forms\Components\Select::make('fornecedores')
                            ->relationship(
                                name: 'fornecedores',
                                titleAttribute: 'nome',
                                modifyQueryUsing: fn (Builder $query) => 
                                    auth()->user()->is_master 
                                        ? $query // Se for master, mostra todos
                                        : $query->where('id_empresa', auth()->user()->id_empresa) // Se não, filtra por empresa
                            )
                            ->label('Fornecedor(es)')
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->required(),
                        Forms\Components\DatePicker::make('data')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('observacao')
                            ->label('Observação')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        Forms\Components\Hidden::make('id_usuario')
                            ->default(fn () => auth()->user()->id),
                    ])->columns(2),

                // ---------------------- SEÇÃO 2 ----------------------
              /*  Forms\Components\Section::make('Importar Itens via CSV')
                    ->description('Faça upload de um arquivo CSV e clique em "Importar Itens" para carregar os dados automaticamente.')
                    ->schema([
                        Forms\Components\FileUpload::make('csv_import')
                            ->label('Arquivo CSV')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->maxSize(1024)
                            ->directory('temp-csv-imports')
                            ->preserveFilenames()
                            ->helperText('Formato: Descrição, Quantidade, Marca (opcional), Observação (opcional)')
                            ->multiple(false)
                            ->storeFiles(false)
                            ->visibility('private'),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('importar')
                                ->label('Importar Itens')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->button()
                                ->color('primary')
                                ->requiresConfirmation()
                                ->modalHeading('Importar Itens do CSV')
                                ->modalDescription('Tem certeza que deseja importar os itens do arquivo CSV? Os itens atuais serão mantidos.')
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    \Log::info('=== INÍCIO DA IMPORTACAO CSV ===');
                                    
                                    $csvFiles = $get('csv_import');
                                    \Log::info('Dados do csv_import:', ['csvFiles' => $csvFiles, 'type' => gettype($csvFiles)]);
                                    
                                    // Verifica se há arquivo
                                    if (empty($csvFiles)) {
                                        \Log::warning('Nenhum arquivo CSV selecionado');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Nenhum arquivo selecionado')
                                            ->body('Por favor, selecione um arquivo CSV antes de importar.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    // O FileUpload retorna uma estrutura específica
                                    // Precisamos extrair o caminho do arquivo
                                    $filePath = self::getFilePathFromUpload($csvFiles);
                                    \Log::info('Caminho do arquivo extraído:', ['filePath' => $filePath]);
                                    
                                    if (!$filePath) {
                                        \Log::error('Não foi possível extrair o caminho do arquivo do upload');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Erro no arquivo')
                                            ->body('Não foi possível acessar o arquivo CSV.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    \Log::info('Iniciando processamento do CSV...');
                                    $importedItems = self::processarCSV($filePath);
                                    \Log::info('Resultado do processamento:', ['count' => count($importedItems), 'items' => $importedItems]);

                                    if (empty($importedItems)) {
                                        \Log::warning('Nenhum item foi importado do CSV');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Nenhum item importado')
                                            ->body('Verifique se o arquivo CSV contém dados válidos no formato correto.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    $currentItems = $get('items') ?? [];
                                    \Log::info('Itens atuais no repeater:', ['count' => count($currentItems)]);
                                    
                                    $set('items', array_merge($currentItems, $importedItems));
                                    \Log::info('Itens após merge:', ['total' => count($currentItems) + count($importedItems)]);

                                    \Filament\Notifications\Notification::make()
                                        ->title('Importação concluída')
                                        ->body(count($importedItems) . ' itens adicionados com sucesso.')
                                        ->success()
                                        ->send();

                                    // Limpa o campo de upload
                                    $set('csv_import', null);
                                    \Log::info('Campo csv_import limpo');
                                    \Log::info('=== FIM DA IMPORTACAO CSV ===');
                                }),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
*/
                // ---------------------- SEÇÃO 3 ----------------------
                Forms\Components\Section::make('Itens da Cotação')
                    ->schema([
                        //---------------------- UOLOAD ITENS VIA CSV-----------------
                        Forms\Components\Section::make('Importar itens em lote')
                            //->description('Faça upload de um arquivo CSV e clique em "Importar Itens" para carregar os dados automaticamente.')
                            ->schema([
                                Forms\Components\FileUpload::make('csv_import')
                                    ->label('Selecione o arquivo com a lista de itens')
                                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                                    ->maxSize(1024)
                                    ->directory('temp-csv-imports')
                                    ->preserveFilenames()
                                    ->helperText('Formato: Descrição, Quantidade, Marca (opcional), Observação (opcional)')
                                    ->multiple(false)
                                    ->storeFiles(false)
                                    ->visibility('private'),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('importar')
                                        ->label('Importar Itens')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->button()
                                        ->color('primary')
                                        ->requiresConfirmation()
                                        ->modalHeading('Importar Itens do CSV')
                                        ->modalDescription('Tem certeza que deseja importar os itens do arquivo CSV? Os itens atuais serão mantidos.')
                                        ->action(function (Forms\Set $set, Forms\Get $get) {
                                            \Log::info('=== INÍCIO DA IMPORTACAO CSV ===');
                                            
                                            $csvFiles = $get('csv_import');
                                            \Log::info('Dados do csv_import:', ['csvFiles' => $csvFiles, 'type' => gettype($csvFiles)]);
                                            
                                            // Verifica se há arquivo
                                            if (empty($csvFiles)) {
                                                \Log::warning('Nenhum arquivo CSV selecionado');
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Nenhum arquivo selecionado')
                                                    ->body('Por favor, selecione um arquivo CSV antes de importar.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            // O FileUpload retorna uma estrutura específica
                                            // Precisamos extrair o caminho do arquivo
                                            $filePath = self::getFilePathFromUpload($csvFiles);
                                            \Log::info('Caminho do arquivo extraído:', ['filePath' => $filePath]);
                                            
                                            if (!$filePath) {
                                                \Log::error('Não foi possível extrair o caminho do arquivo do upload');
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Erro no arquivo')
                                                    ->body('Não foi possível acessar o arquivo CSV.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            \Log::info('Iniciando processamento do CSV...');
                                            $importedItems = self::processarCSV($filePath);
                                            \Log::info('Resultado do processamento:', ['count' => count($importedItems), 'items' => $importedItems]);

                                            if (empty($importedItems)) {
                                                \Log::warning('Nenhum item foi importado do CSV');
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Nenhum item importado')
                                                    ->body('Verifique se o arquivo CSV contém dados válidos no formato correto.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $currentItems = $get('items') ?? [];
                                            \Log::info('Itens atuais no repeater:', ['count' => count($currentItems)]);
                                            
                                            $set('items', array_merge($currentItems, $importedItems));
                                            \Log::info('Itens após merge:', ['total' => count($currentItems) + count($importedItems)]);

                                            \Filament\Notifications\Notification::make()
                                                ->title('Importação concluída')
                                                ->body(count($importedItems) . ' itens adicionados com sucesso.')
                                                ->success()
                                                ->send();

                                            // Limpa o campo de upload
                                            $set('csv_import', null);
                                            \Log::info('Campo csv_import limpo');
                                            \Log::info('=== FIM DA IMPORTACAO CSV ===');
                                        }),
                                ]),
                            ])
                            ->collapsible()
                            ->collapsed(),
                        
                         
                        //---------------------- REPEATER ITENS ----------------------
                        Forms\Components\Repeater::make('items')
                            ->label('Itens')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('id_produto')
                                    ->relationship('produto', 'descricao')
                                    ->searchable()
                                    ->columnSpan(2)
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $produto = \App\Models\Produto::find($state);
                                            if ($produto) {
                                                $set('descricao_produto', $produto->descricao);
                                                $set('id_marca', $produto->id_marca);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('descricao_produto')
                                    ->label('Descrição')
                                    ->columnSpan(3)
                                    ->maxLength(255),

                                Forms\Components\Select::make('id_marca')
                                    ->relationship('marca', 'nome')
                                    ->searchable()
                                    ->columnSpan(2)
                                    ->preload(),

                                Forms\Components\TextInput::make('quantidade')
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('observacao')
                                    ->label('Observação')
                                    ->columnSpan(2)
                                    ->rows(1)
                                    ->maxLength(200),
                            ])
                            ->columns(10)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar Item'),
                    ]),
            ]);
    }

    /**
     * Extrai o caminho do arquivo do resultado do FileUpload
     * Agora lida corretamente com a estrutura do Livewire TemporaryUploadedFile
     */
    private static function getFilePathFromUpload($uploadData): ?string
    {
        \Log::info('getFilePathFromUpload - Input:', ['uploadData' => $uploadData, 'type' => gettype($uploadData)]);
        
        // Se já é uma string (caminho direto)
        if (is_string($uploadData)) {
            \Log::info('getFilePathFromUpload - Retornando string:', ['path' => $uploadData]);
            return $uploadData;
        }

        // Se é um array, procura pelo caminho do arquivo
        if (is_array($uploadData)) {
            \Log::info('getFilePathFromUpload - Processando array:', ['count' => count($uploadData), 'keys' => array_keys($uploadData)]);
            
            // Estrutura: [uuid => [TemporaryUploadedFile => path]] ou [uuid => TemporaryUploadedFile]
            foreach ($uploadData as $uuid => $fileData) {
                \Log::info('getFilePathFromUpload - Analisando UUID:', ['uuid' => $uuid, 'fileData' => $fileData]);
                
                // Se fileData é um TemporaryUploadedFile
                if ($fileData instanceof TemporaryUploadedFile) {
                    $path = $fileData->getRealPath();
                    \Log::info('getFilePathFromUpload - TemporaryUploadedFile encontrado:', ['path' => $path]);
                    return $path;
                }
                
                // Se fileData é um array que contém TemporaryUploadedFile
                if (is_array($fileData)) {
                    foreach ($fileData as $key => $value) {
                        \Log::info('getFilePathFromUpload - Analisando item do array:', ['key' => $key, 'value' => $value]);
                        
                        // Se encontrou o TemporaryUploadedFile
                        if ($value instanceof TemporaryUploadedFile) {
                            $path = $value->getRealPath();
                            \Log::info('getFilePathFromUpload - TemporaryUploadedFile no array:', ['path' => $path]);
                            return $path;
                        }
                        
                        // Se o valor é diretamente o caminho (fallback)
                        if (is_string($value) && file_exists($value)) {
                            \Log::info('getFilePathFromUpload - Caminho direto encontrado:', ['path' => $value]);
                            return $value;
                        }
                    }
                }
                
                // Se fileData é diretamente o caminho (fallback)
                if (is_string($fileData) && file_exists($fileData)) {
                    \Log::info('getFilePathFromUpload - Caminho direto no fileData:', ['path' => $fileData]);
                    return $fileData;
                }
            }
        }

        // Tentativa final: usar reflection para acessar propriedades protegidas
        if (is_array($uploadData)) {
            foreach ($uploadData as $uuid => $fileData) {
                if (is_array($fileData)) {
                    foreach ($fileData as $key => $value) {
                        if (is_object($value)) {
                            try {
                                $reflection = new \ReflectionClass($value);
                                if ($reflection->hasProperty('fileName')) {
                                    $property = $reflection->getProperty('fileName');
                                    $property->setAccessible(true);
                                    $fileName = $property->getValue($value);
                                    \Log::info('getFilePathFromUpload - Reflection fileName:', ['fileName' => $fileName]);
                                    
                                    if ($fileName && file_exists($fileName)) {
                                        return $fileName;
                                    }
                                }
                                
                                if ($reflection->hasProperty('path')) {
                                    $property = $reflection->getProperty('path');
                                    $property->setAccessible(true);
                                    $path = $property->getValue($value);
                                    \Log::info('getFilePathFromUpload - Reflection path:', ['path' => $path]);
                                    
                                    if ($path && file_exists($path)) {
                                        return $path;
                                    }
                                }
                            } catch (\Throwable $e) {
                                \Log::warning('getFilePathFromUpload - Reflection error:', ['error' => $e->getMessage()]);
                            }
                        }
                    }
                }
            }
        }

        \Log::warning('getFilePathFromUpload - Não foi possível extrair caminho do arquivo');
        \Log::warning('getFilePathFromUpload - Estrutura completa:', ['structure' => $uploadData]);
        return null;
    }

    // ---------------------- PROCESSAMENTO CSV ----------------------
    private static function processarCSV(string $filePath): array
    {
        \Log::info('=== INÍCIO PROCESSARCSV ===');
        \Log::info('processarCSV - FilePath recebido:', ['filePath' => $filePath]);
        
        try {
            // Verifica se é um caminho absoluto do sistema de arquivos
            if (file_exists($filePath)) {
                $fullPath = $filePath;
                \Log::info('processarCSV - Usando caminho absoluto:', ['fullPath' => $fullPath]);
            } else {
                // Tenta como caminho do Storage
                $fullPath = Storage::path($filePath);
                \Log::info('processarCSV - Convertendo para Storage path:', ['fullPath' => $fullPath]);
            }
            
            if (!file_exists($fullPath)) {
                \Log::error("processarCSV - Arquivo não encontrado: {$fullPath}");
                return [];
            }

            // Verifica se o arquivo não está vazio
            $fileSize = filesize($fullPath);
            \Log::info('processarCSV - Tamanho do arquivo:', ['size' => $fileSize]);
            
            if ($fileSize === 0) {
                \Log::warning("processarCSV - Arquivo CSV vazio: {$fullPath}");
                return [];
            }

            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setDelimiter(',');
            
            // Tenta detectar automaticamente o cabeçalho
            $csv->setHeaderOffset(0);
            
            $header = $csv->getHeader();
            \Log::info('processarCSV - Cabeçalhos detectados:', ['headers' => $header]);
            
            $records = $csv->getRecords();
            $importedItems = [];
            $linhaNumero = 1;

            foreach ($records as $record) {
                $linhaNumero++;
                \Log::info("processarCSV - Processando linha {$linhaNumero}:", ['record' => $record]);
                
                // Tenta diferentes nomes de colunas possíveis
                $descricao = trim(
                    $record['descricao'] ?? 
                    $record['Descrição'] ?? 
                    $record['Descricao'] ??
                    $record['produto'] ?? 
                    $record['Produto'] ?? 
                    $record['item'] ?? 
                    $record['Item'] ?? 
                    $record['nome'] ?? 
                    $record['Nome'] ?? 
                    ''
                );

                $quantidade = trim(
                    $record['quantidade'] ?? 
                    $record['Quantidade'] ?? 
                    $record['qtd'] ?? 
                    $record['Qtd'] ?? 
                    $record['qtd.'] ?? 
                    '1'
                );

                $marca = trim(
                    $record['marca'] ?? 
                    $record['Marca'] ?? 
                    $record['brand'] ?? 
                    $record['Brand'] ?? 
                    ''
                );

                $observacao = trim(
                    $record['observacao'] ?? 
                    $record['Observação'] ?? 
                    $record['Observacao'] ?? 
                    $record['obs'] ?? 
                    $record['Obs'] ?? 
                    $record['nota'] ?? 
                    $record['Nota'] ?? 
                    $record['comentario'] ??
                    ''
                );

                \Log::info("processarCSV - Dados extraídos linha {$linhaNumero}:", [
                    'descricao' => $descricao,
                    'quantidade' => $quantidade,
                    'marca' => $marca,
                    'observacao' => $observacao
                ]);

                // Pula linhas vazias
                if (empty($descricao)) {
                    \Log::warning("processarCSV - Linha {$linhaNumero} ignorada (descrição vazia)");
                    continue;
                }

                // Converte quantidade para float
                $quantidade = floatval(str_replace(',', '.', str_replace('.', '', $quantidade)));
                if ($quantidade <= 0) {
                    $quantidade = 1;
                }
                
                \Log::info("processarCSV - Quantidade convertida:", ['original' => $record['quantidade'] ?? '', 'convertida' => $quantidade]);

                // Busca marca e produto
                $user = auth()->user();
                $idMarca = null;
                $idProduto = null;

                if (!empty($marca)) {
                    \Log::info("processarCSV - Buscando marca:", ['marca' => $marca]);
                    $idMarca = \App\Models\Marca::where('nome', 'like', '%' . $marca . '%')
                        ->where('id_empresa', $user->id_empresa)
                        ->value('id');
                    \Log::info("processarCSV - Resultado busca marca:", ['id_marca' => $idMarca]);
                }

                if (!empty($descricao)) {
                    \Log::info("processarCSV - Buscando produto:", ['descricao' => $descricao]);
                    $idProduto = \App\Models\Produto::where('descricao', 'like', '%' . $descricao . '%')
                        ->where('id_empresa', $user->id_empresa)
                        ->value('id');
                    \Log::info("processarCSV - Resultado busca produto:", ['id_produto' => $idProduto]);
                }

                $item = [
                    'id_produto' => $idProduto,
                    'descricao_produto' => $descricao,
                    'id_marca' => $idMarca,
                    'quantidade' => $quantidade,
                    'observacao' => $observacao,
                ];
                
                $importedItems[] = $item;
                \Log::info("processarCSV - Item adicionado:", $item);
            }

            \Log::info('processarCSV - Importação concluída:', [
                'arquivo' => $filePath,
                'itens_importados' => count($importedItems)
            ]);

            \Log::info('=== FIM PROCESSARCSV ===');
            return $importedItems;

        } catch (\Throwable $e) {
            \Log::error('processarCSV - Erro ao processar CSV: ' . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            \Log::info('=== FIM PROCESSARCSV COM ERRO ===');
            return [];
        }
    }

    // ---------------------- TABELA ----------------------
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('fornecedores.nome')
                    ->label('Fornecedor(es)')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data')->date(format: 'd/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('valor_total')->money('BRL')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'gray',
                        'enviada' => 'warning',
                        'respondida' => 'success',
                        'finalizada' => 'primary',
                        'cancelada' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')->label('Qtd Itens')->counts('items')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'enviada' => 'Enviada',
                        'respondida' => 'Respondida',
                        'finalizada' => 'Finalizada',
                        'cancelada' => 'Cancelada',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('processar_respostas')
                    ->label('')
                    ->tooltip('Processar Respostas')
                    ->icon('heroicon-o-inbox')
                    ->color('warning')
                    ->action(function () {
                        try {
                            $emailService = new \App\Services\EmailService();
                            $resultados = $emailService->processarRespostasFornecedores();
                            
                            $mensagens = [];
                            foreach ($resultados as $resultado) {
                                $status = $resultado['success'] ? '✅' : '❌';
                                $mensagens[] = "{$status} {$resultado['message']}";
                            }
                            
                            $mensagemFinal = "Processamento de respostas:\n" . implode("\n", $mensagens);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Processamento Concluído')
                                ->body($mensagemFinal)
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro no Processamento')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('visualizar_respostas')
                    ->label('')
                    ->tooltip('Visualizar Respostas')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modal()
                    ->modalContent(fn (Cotacao $record) => view('filament.resources.cotacao-resource.pages.respostas-fornecedores', [
                        'cotacao' => $record,
                    ]))
                    ->visible(fn (Cotacao $record) => $record->fornecedores()->wherePivot('status', 'respondida')->exists()),
                                Tables\Actions\Action::make('enviar_todos')
                                    ->label('')
                                    ->tooltip('Enviar Todos')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->color('success')
                                    ->action(function (Cotacao $record) {
                                        $emailService = new EmailService();
                                        $resultados = [];
                                        
                                        foreach ($record->fornecedores as $fornecedor) {
                                            $resultado = $emailService->enviarCotacaoParaFornecedor($record, $fornecedor->id);
                                            $resultados[] = "{$fornecedor->nome}: " . ($resultado['success'] ? '✅' : '❌ ' . $resultado['message']);
                                        }

                                        // Mostrar resultados
                                        $mensagem = "Resultados do envio:\n" . implode("\n", $resultados);
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->title('Envio de Cotações')
                                            ->body($mensagem)
                                            ->success()
                                            ->send();
                                    })
                                    ->visible(fn (Cotacao $record) => $record->status === 'pendente'),

                Tables\Actions\Action::make('enviar_individual')
                    ->label('')
                    ->tooltip('Enviar p/ Fornecedor')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->action(function (Cotacao $record, array $data) {
                        $emailService = new EmailService();
                        $resultado = $emailService->enviarCotacaoParaFornecedor($record, $data['fornecedor_id']);
                        
                        if ($resultado['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sucesso')
                                ->body('Cotação enviada com sucesso!')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro no Envio')
                                ->body($resultado['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Select::make('fornecedor_id')
                            ->label('Fornecedor')
                            ->options(fn (Cotacao $record) => $record->fornecedores->pluck('nome', 'id'))
                            ->required(),
                    ])
                    ->visible(fn (Cotacao $record) => $record->status === 'pendente'),

                
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ]);
    }

    // ---------------------- PÁGINAS ----------------------
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotacoes::route('/'),
            'create' => Pages\CreateCotacao::route('/create'),
            'edit' => Pages\EditCotacao::route('/{record}/edit'),
        ];
    }

    // ---------------------- QUERY ----------------------
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if ($user->is_master) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->where('id_empresa', $user->id_empresa);
    }
}