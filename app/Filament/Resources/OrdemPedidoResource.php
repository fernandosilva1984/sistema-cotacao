<?php
// app/Filament/Resources/OrdemPedidoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdemPedidoResource\Pages;
use App\Models\OrdemPedido;
use App\Models\Cotacao;
use App\Models\OrdemPedidoItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrdemPedidoResource extends Resource
{
    protected static ?string $model = OrdemPedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $pluralModelLabel = 'Ordens de Pedido';
    protected static ?string $navigationGroup = 'Operacional';

    // Método para gerar números únicos em lote
    private static function gerarNumerosOrdemPedido(int $quantidade): array
    {
        $ano = date('Y');
        $ultimaOrdem = OrdemPedido::where('numero', 'like', "OP{$ano}%")
            ->orderBy('numero', 'desc')
            ->first();

        $ultimoNumero = $ultimaOrdem ? (int) Str::after($ultimaOrdem->numero, "OP{$ano}") : 0;
        
        $numeros = [];
        for ($i = 1; $i <= $quantidade; $i++) {
            $novoNumero = $ultimoNumero + $i;
            $numeros[] = "OP{$ano}" . str_pad($novoNumero, 5, '0', STR_PAD_LEFT);
        }
        
        return $numeros;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seleção da Cotação')
                    ->schema([
                        Forms\Components\Select::make('id_cotacao')
                            ->label('Cotação')
                            ->relationship(
                                name: 'cotacao',
                                titleAttribute: 'numero',
                                modifyQueryUsing: fn (Builder $query) => 
                                    auth()->user()->is_master 
                                        ? $query // Se for master, mostra todos
                                        : $query->where('id_empresa', auth()->user()->id_empresa) // Se não, filtra por empresa
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('ordens_por_fornecedor', []);
                                }
                            }),
                        
                        Forms\Components\DatePicker::make('data')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\Textarea::make('observacao_geral')
                            ->label('Observações Gerais (aplicam-se a todas as ordens)')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        
                        Forms\Components\Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        
                        Forms\Components\Hidden::make('id_usuario')
                            ->default(fn () => auth()->user()->id),
                    ])->columns(2),
                
                Forms\Components\Section::make('Seleção de Itens por Fornecedor')
                    ->schema([
                        Forms\Components\Placeholder::make('info_itens')
                            ->content(function (Forms\Get $get) {
                                $idCotacao = $get('id_cotacao');
                                if (!$idCotacao) {
                                    return 'Selecione uma cotação para visualizar as respostas dos fornecedores.';
                                }
                                
                                $cotacao = Cotacao::find($idCotacao);
                                return $cotacao ? "Cotação {$cotacao->numero} - Selecione os itens para gerar as ordens de pedido" : 'Cotação não encontrada';
                            }),
                        
                        Forms\Components\Placeholder::make('info_multiplas_ordens')
                            ->label('')
                            ->content('💡 **Atenção**: Itens de fornecedores diferentes serão agrupados em ordens de pedido separadas automaticamente.')
                            ->columnSpanFull(),
                        
                        // Componente dinâmico para cada fornecedor
                        Forms\Components\Grid::make()
                            ->schema(function (Forms\Get $get) {
                                $idCotacao = $get('id_cotacao');
                                if (!$idCotacao) {
                                    return [Forms\Components\Placeholder::make('no_cotacao')->content('Nenhuma cotação selecionada')];
                                }
                                
                                try {
                                    $cotacao = Cotacao::with(['fornecedores', 'items.produto', 'items.marca'])->find($idCotacao);
                                    if (!$cotacao) {
                                        return [Forms\Components\Placeholder::make('cotacao_nao_encontrada')->content('Cotação não encontrada')];
                                    }
                                    
                                    $schemas = [];
                                    
                                    foreach ($cotacao->fornecedores as $fornecedor) {
                                        if ($fornecedor->pivot->status === 'respondida') {
                                            $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                                            
                                            if (!is_array($valores)) {
                                                continue;
                                            }
                                            
                                            $itemSchemas = [];
                                            $itemIndex = 0;
                                            $totalFornecedor = 0;
                                            
                                            foreach ($cotacao->items as $cotacaoItem) {
                                                if (isset($valores[$itemIndex])) {
                                                    $valorUnitario = $valores[$itemIndex];
                                                    $valorTotal = $cotacaoItem->quantidade * $valorUnitario;
                                                    $totalFornecedor += $valorTotal;
                                                    
                                                    // Criar uma linha em grid para cada item (simulando tabela)
                                                    $itemSchemas[] = Forms\Components\Grid::make(7)
                                                        ->schema([
                                                            Forms\Components\Checkbox::make("item_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->reactive()
                                                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                                    self::atualizarOrdensPorFornecedor($set, $get);
                                                                }),
                                                            Forms\Components\Placeholder::make("desc_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->content($cotacaoItem->descricao_produto)
                                                                ->extraAttributes(['class' => 'font-bold'])
                                                                ->columnSpan(2),
                                                            Forms\Components\Placeholder::make("marca_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->content($cotacaoItem->marca->nome ?? 'N/A')
                                                                ->extraAttributes(['class' => 'font-bold'])
                                                                ->columnSpan(1),
                                                            Forms\Components\Placeholder::make("qtd_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->content(number_format($cotacaoItem->quantidade, 0, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-center']),
                                                            Forms\Components\Placeholder::make("unit_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->content('R$ ' . number_format($valorUnitario, 2, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-right']),
                                                            Forms\Components\Placeholder::make("total_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->label('')
                                                                ->content('R$ ' . number_format($valorTotal, 2, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-right font-bold']),
                                                        ])
                                                        ->columns(7);
                                                }
                                                $itemIndex++;
                                            }
                                            
                                            if (!empty($itemSchemas)) {
                                                // Adicionar cabeçalho da "tabela"
                                                array_unshift($itemSchemas, 
                                                    Forms\Components\Grid::make(7)
                                                        ->schema([
                                                            Forms\Components\Placeholder::make('header_sel')
                                                                ->label('')
                                                                ->content('SELECIONAR')
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm']),
                                                            Forms\Components\Placeholder::make('header_desc')
                                                                ->label('')
                                                                ->content('PRODUTO')
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm'])
                                                                ->columnSpan(2),
                                                            Forms\Components\Placeholder::make('header_marca')
                                                                ->label('')
                                                                ->content('MARCA')
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm'])
                                                                ->columnSpan(1),
                                                            Forms\Components\Placeholder::make('header_qtd')
                                                                ->label('')
                                                                ->content('QTD')
                                                                ->extraAttributes(['class' => 'text-center font-bold uppercase text-sm']),
                                                            Forms\Components\Placeholder::make('header_unit')
                                                                ->label('')
                                                                ->content('UNITÁRIO')
                                                                ->extraAttributes(['class' => 'text-right font-bold uppercase text-sm']),
                                                            Forms\Components\Placeholder::make('header_total')
                                                                ->label('')
                                                                ->content('TOTAL')
                                                                ->extraAttributes(['class' => 'text-right font-bold uppercase text-sm']),
                                                        ])
                                                        ->columns(7)
                                                );
                                                
                                                $schemas[] = Forms\Components\Section::make("{$fornecedor->nome}")
                                                    ->description(function () use ($totalFornecedor) {
                                                        return 'Total potencial: R$ ' . number_format($totalFornecedor, 2, ',', '.');
                                                    })
                                                    ->schema($itemSchemas)
                                                    ->collapsible();
                                            }
                                        }
                                    }
                                    
                                    if (empty($schemas)) {
                                        return [Forms\Components\Placeholder::make('sem_respostas')->content('Nenhum fornecedor respondeu esta cotação ainda.')];
                                    }
                                    
                                    return $schemas;
                                    
                                } catch (\Exception $e) {
                                    return [Forms\Components\Placeholder::make('erro')->content('Erro ao carregar dados da cotação')];
                                }
                            }),
                    ])
                    ->visible(fn (Forms\Get $get): bool => !is_null($get('id_cotacao'))),
                
                Forms\Components\Section::make('Pré-visualização das Ordens de Pedido')
                    ->schema([
                        Forms\Components\Placeholder::make('info_preview')
                            ->content(function (Forms\Get $get) {
                                $ordens = $get('ordens_por_fornecedor') ?? [];
                                $totalOrdens = count($ordens);
                                
                                if ($totalOrdens === 0) {
                                    return 'Selecione itens acima para visualizar as ordens de pedido que serão criadas.';
                                }
                                
                                return "Serão criadas {$totalOrdens} ordem(ns) de pedido:";
                            }),
                        
                        Forms\Components\Grid::make()
                            ->schema(function (Forms\Get $get) {
                                $ordens = $get('ordens_por_fornecedor') ?? [];
                                $schemas = [];
                                
                                foreach ($ordens as $fornecedorId => $ordemData) {
                                    $fornecedorNome = $ordemData['fornecedor_nome'];
                                    $totalOrdem = $ordemData['total'];
                                    $quantidadeItens = count($ordemData['itens']);
                                    
                                    $schemas[] = Forms\Components\Card::make()
                                        ->schema([
                                            Forms\Components\Placeholder::make("fornecedor_{$fornecedorId}")
                                                ->label("Ordem para: {$fornecedorNome}")
                                                ->content(
                                                    "{$quantidadeItens} item(s) | " .
                                                    "Total: R$ " . number_format($totalOrdem, 2, ',', '.')
                                                ),
                                            
                                            Forms\Components\Textarea::make("observacao_fornecedor_{$fornecedorId}")
                                                ->label("Observações específicas para {$fornecedorNome}")
                                                ->placeholder("Observações específicas para esta ordem...")
                                                ->maxLength(65535),
                                        ]);
                                }
                                
                                return $schemas;
                            }),
                    ])
                    ->visible(fn (Forms\Get $get): bool => !empty($get('ordens_por_fornecedor'))),
                
                // Campo hidden para armazenar a estrutura das ordens
                Forms\Components\Hidden::make('ordens_por_fornecedor')
                    ->reactive()
                    ->default([]),
            ]);
    }

    // Método para agrupar itens por fornecedor (mantido igual)
    private static function atualizarOrdensPorFornecedor(Forms\Set $set, Forms\Get $get)
    {
        $idCotacao = $get('id_cotacao');
        if (!$idCotacao) {
            $set('ordens_por_fornecedor', []);
            return;
        }
        
        try {
            $cotacao = Cotacao::with(['fornecedores', 'items.produto', 'items.marca'])->find($idCotacao);
            if (!$cotacao) {
                $set('ordens_por_fornecedor', []);
                return;
            }
            
            $ordensPorFornecedor = [];
            
            // Agrupar itens selecionados por fornecedor
            foreach ($cotacao->fornecedores as $fornecedor) {
                if ($fornecedor->pivot->status === 'respondida') {
                    $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                    
                    if (!is_array($valores)) {
                        continue;
                    }
                    
                    $itensFornecedor = [];
                    $totalFornecedor = 0;
                    $itemIndex = 0;
                    
                    foreach ($cotacao->items as $cotacaoItem) {
                        if (isset($valores[$itemIndex])) {
                            $checkboxName = "item_{$fornecedor->id}_{$cotacaoItem->id}";
                            $isSelecionado = $get($checkboxName) ?? false;
                            
                            if ($isSelecionado) {
                                $valorUnitario = $valores[$itemIndex];
                                $valorTotal = $cotacaoItem->quantidade * $valorUnitario;
                                
                                $itensFornecedor[] = [
                                    'id_cotacao_item' => $cotacaoItem->id,
                                    'id_produto' => $cotacaoItem->id_produto,
                                    'descricao_produto' => $cotacaoItem->descricao_produto,
                                    'id_marca' => $cotacaoItem->id_marca,
                                    'quantidade' => $cotacaoItem->quantidade,
                                    'valor_unitario' => $valorUnitario,
                                    'valor_total_item' => $valorTotal,
                                    'observacao' => $cotacaoItem->observacao ?? '',
                                ];
                                
                                $totalFornecedor += $valorTotal;
                            }
                        }
                        $itemIndex++;
                    }
                    
                    if (!empty($itensFornecedor)) {
                        $ordensPorFornecedor[$fornecedor->id] = [
                            'fornecedor_nome' => $fornecedor->nome,
                            'fornecedor_id' => $fornecedor->id,
                            'itens' => $itensFornecedor,
                            'total' => $totalFornecedor,
                        ];
                    }
                }
            }
            
            $set('ordens_por_fornecedor', $ordensPorFornecedor);
            
        } catch (\Exception $e) {
            $set('ordens_por_fornecedor', []);
        }
    }

    // Sobrescrever o método create para criar múltiplas ordens
    public static function createMultipleOrders(array $data): void
    {
        $cotacao = Cotacao::find($data['id_cotacao']);
        $observacaoGeral = $data['observacao_geral'] ?? '';
        $quantidadeOrdens = count($data['ordens_por_fornecedor']);
        
        // Gerar todos os números de uma vez
        $numerosOrdem = self::gerarNumerosOrdemPedido($quantidadeOrdens);
        $indiceNumero = 0;
        
        foreach ($data['ordens_por_fornecedor'] as $fornecedorId => $ordemData) {
            // Usar o próximo número disponível
            $numeroOrdem = $numerosOrdem[$indiceNumero];
            $indiceNumero++;
            
            // Criar ordem de pedido para cada fornecedor
            $ordemPedido = OrdemPedido::create([
                'numero' => $numeroOrdem,
                'id_cotacao' => $data['id_cotacao'],
                'id_fornecedor' => $fornecedorId,
                'data' => $data['data'],
                'observacao' => trim($observacaoGeral . "\n\n" . ($data["observacao_fornecedor_{$fornecedorId}"] ?? '')),
                'valor_total' => $ordemData['total'],
                'status' => 'pendente',
                'id_empresa' => $data['id_empresa'],
                'id_usuario' => $data['id_usuario'],
            ]);
            
            // Criar itens da ordem de pedido
            foreach ($ordemData['itens'] as $itemData) {
                OrdemPedidoItem::create([
                    'id_ordem_pedido' => $ordemPedido->id,
                    'id_produto' => $itemData['id_produto'],
                    'descricao_produto' => $itemData['descricao_produto'],
                    'id_marca' => $itemData['id_marca'],
                    'quantidade' => $itemData['quantidade'],
                    'valor_unitario' => $itemData['valor_unitario'],
                    'valor_total' => $itemData['valor_total_item'],
                    'observacao' => $itemData['observacao'],
                ]);
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fornecedor.nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cotacao.numero')
                    ->searchable()
                    ->sortable()
                    ->label('Cotação'),
                Tables\Columns\TextColumn::make('data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valor_total')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'gray',
                        'aprovada' => 'warning',
                        'entregue' => 'success',
                        'cancelada' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Criado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fornecedor')
                    ->relationship('fornecedor', 'nome')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('cotacao')
                    ->relationship('cotacao', 'numero')
                    ->searchable()
                    ->preload()
                    ->label('Cotação'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovada' => 'Aprovada',
                        'entregue' => 'Entregue',
                        'cancelada' => 'Cancelada',
                    ]),
                Tables\Filters\Filter::make('data')
                    ->form([
                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data Início'),
                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data Fim'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicio'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data', '>=', $date),
                            )
                            ->when(
                                $data['data_fim'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (OrdemPedido $record) {
                        $record->status = 'aprovada';
                        $record->save();
                    })
                    ->visible(fn (OrdemPedido $record) => $record->status === 'pendente'),
                
                Tables\Actions\Action::make('entregue')
                    ->label('Entregue')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->action(function (OrdemPedido $record) {
                        $record->status = 'entregue';
                        $record->save();
                    })
                    ->visible(fn (OrdemPedido $record) => $record->status === 'aprovada'),
                
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdemPedidos::route('/'),
            'create' => Pages\CreateOrdemPedido::route('/create'),
            'edit' => Pages\EditOrdemPedido::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if ($user->is_master) {
            return parent::getEloquentQuery();
        }
        
        return parent::getEloquentQuery()->where('id_empresa', $user->id_empresa);
    }
}