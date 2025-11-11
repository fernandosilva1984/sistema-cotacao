<?php
// app/Filament/Resources/EmpresaResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpresaResource\Pages;
use App\Filament\Resources\EmpresaResource\RelationManagers;
use App\Models\Empresa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmpresaResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Administração';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Empresa')
                    ->schema([
                        Forms\Components\TextInput::make('razao_social')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nome_fantasia')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cnpj')
                            ->required()
                            ->maxLength(18)
                            ->mask('99.999.999/9999-99')
                            ->unique(ignoreRecord: true),
                    ])->columns(2),
                
                Forms\Components\Section::make('Contato')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contato')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('endereco')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bairro')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cidade')
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),
                
                Forms\Components\Section::make('Configurações de Email')
                    ->schema([
                        Forms\Components\TextInput::make('email_host')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email_port')
                            ->numeric()
                            ->maxLength(5),
                        Forms\Components\TextInput::make('email_username')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email_password')
                            ->password()
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Toggle::make('status')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome_fantasia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razao_social')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cnpj')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('ativas')
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpresas::route('/'),
            'create' => Pages\CreateEmpresa::route('/create'),
            'edit' => Pages\EditEmpresa::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->is_master;
    }
}