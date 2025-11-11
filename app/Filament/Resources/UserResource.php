<?php
// app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\ImportacaoCsv;
use App\Filament\Imports\UserImporter;

class UserResource extends Resource
{
    use ImportacaoCsv;
    protected static ?string $model = User::class;

    protected static ?string $slug = 'usuarios';
    protected static ?string $navigationLabel = 'Usuários';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $pluralModelLabel = 'Usuários';
    protected static ?string $navigationGroup = 'Administração';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Usuário')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->hiddenOn('edit')
                            ->dehydrated(fn ($state) => filled($state)),
                    ])->columns(2),
                
                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Select::make('id_empresa')
                            ->relationship('empresa', 'nome_fantasia')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Toggle::make('status')
                            ->required()
                            ->default(true),
                        Forms\Components\Toggle::make('is_master')
                            ->label('Administrador Master')
                            ->required()
                            ->default(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('empresa.nome_fantasia')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_master')
                    ->label('Master')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('master')
                    ->query(fn (Builder $query): Builder => $query->where('is_master', true)),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            //'import' => Pages\ImportUser::route('/import'),
        ];
    }
     public function getTipoImportacao(): string
    {
        return 'usuarios';
    }
    public static function getHeaderActions(): array
    {
        return [
             Actions\ImportAction::make()
                ->importer(UserImporter::class)
                ->tooltip('Importar Usuários via CSV')
                ->icon('heroicon-o-inbox')
                ->label('')
                ->color('success'),
            Actions\CreateAction::make(),
           
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->is_master;
    }
}