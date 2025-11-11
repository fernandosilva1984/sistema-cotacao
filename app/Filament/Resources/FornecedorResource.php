<?php
// app/Filament/Resources/FornecedorResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\FornecedorResource\Pages;
use App\Models\Fornecedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedor::class;

    protected static ?string $slug = 'fornecedores';
    protected static ?string $navigationLabel = 'Fornecedores';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $pluralModelLabel = 'Fornecedores';
    protected static ?string $navigationGroup = 'Cadastros';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Fornecedor')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Informações Legais')
                    ->schema([
                        Forms\Components\TextInput::make('razao_social')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nome_fantasia')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('endereco')
                            ->maxLength(255),
                    ]),
                
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
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razao_social')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nome_fantasia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ListFornecedores::route('/'),
            'create' => Pages\CreateFornecedor::route('/create'),
            'edit' => Pages\EditFornecedor::route('/{record}/edit'),
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