<?php
// app/Models/Empresa.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'endereco',
        'bairro',
        'cidade',
        'cnpj',
        'contato',
        'email',
        'email_host',
        'email_port',
        'email_username',
        'email_password',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_empresa');
    }

    public function fornecedores()
    {
        return $this->hasMany(Fornecedor::class, 'id_empresa');
    }

    public function marcas()
    {
        return $this->hasMany(Marca::class, 'id_empresa');
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class, 'id_empresa');
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'id_empresa');
    }

    public function ordensPedido()
    {
        return $this->hasMany(OrdemPedido::class, 'id_empresa');
    }
}