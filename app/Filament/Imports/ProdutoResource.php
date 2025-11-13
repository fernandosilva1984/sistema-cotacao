<?php
// app/Filament/Resources/ProdutoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Cadastros';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Produto')
                    ->schema([
                        Forms\Components\TextInput::make('descricao')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('id_marca')
                            ->relationship('marca', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nome')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('status')
                                    ->default(true),
                            ]),
                        Forms\Components\TextInput::make('codigo_barras')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Select::make('unidade_medida')
                            ->options([
                                'UN' => 'Unidade',
                                'PC' => 'Peça',
                                'KG' => 'Quilograma',
                                'GR' => 'Grama',
                                'LT' => 'Litro',
                                'ML' => 'Mililitro',
                                'M' => 'Metro',
                                'CM' => 'Centímetro',
                            ])
                            ->default('UN')
                            ->required(),
                        Forms\Components\Textarea::make('observacao')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Toggle::make('status')
                    ->required()
                    ->default(true),
                Forms\Components\Hidden::make('id_empresa')
                    ->default(fn () => auth()->user()->id_empresa),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('descricao')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca.nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_barras')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unidade_medida')
                    ->badge(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('marca')
                    ->relationship('marca', 'nome')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('ativos')
                    ->query(fn (Builder $query): Builder => $query->where('status', true)),
            ])
            ->actions([
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
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
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