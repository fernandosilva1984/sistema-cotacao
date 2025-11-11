<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'id_empresa',
        'status',
        'is_master',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'is_master' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status && ($this->is_master || $this->empresa);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'id_usuario');
    }

    public function ordensPedido()
    {
        return $this->hasMany(OrdemPedido::class, 'id_usuario');
    }

    public function isMaster(): bool
    {
        return $this->is_master;
    }
}