<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoModeloCampo extends Model
{
    protected $table = 'peticao_modelo_campos';

    protected $fillable = [
        'modelo_id',
        'legacy_input_id',
        'rotulo',
        'token',
        'tipo',
        'origem_coluna',
        'origem_alias',
        'prefixo',
        'sufixo',
        'valor_padrao',
        'classe_css',
        'largura',
        'colunas_layout',
        'linhas_layout',
        'ordem',
        'obrigatorio',
        'visivel',
        'gera_nome_arquivo',
        'eventos_frontend',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'visivel' => 'boolean',
        'gera_nome_arquivo' => 'boolean',
        'eventos_frontend' => 'array',
    ];

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function opcoes()
    {
        return $this->hasMany(PeticaoModeloCampoOpcao::class, 'campo_id')->orderBy('ordem');
    }
}
