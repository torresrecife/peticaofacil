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

    public function getIdInputAttribute()
    {
        return $this->legacy_input_id ?: $this->id;
    }

    public function getInputTitleAttribute()
    {
        return $this->rotulo;
    }

    public function getInputTipoAttribute()
    {
        return $this->tipo;
    }

    public function getInputValAttribute()
    {
        return $this->origem_coluna;
    }

    public function getInputDbAttribute()
    {
        return $this->origem_alias;
    }

    public function getInputPreAttribute()
    {
        return $this->prefixo;
    }

    public function getInputPosAttribute()
    {
        return $this->sufixo;
    }

    public function getInputColsAttribute()
    {
        return $this->colunas_layout ?: 1;
    }

    public function getInputRolsAttribute()
    {
        return $this->linhas_layout ?: 0;
    }

    public function getInputWidthAttribute()
    {
        return $this->largura;
    }

    public function getInputReqAttribute()
    {
        return $this->obrigatorio ? '1' : '0';
    }

    public function getInputOrderAttribute()
    {
        return $this->ordem;
    }

    public function getNomepetAttribute()
    {
        return $this->gera_nome_arquivo ? 'Y' : 'N';
    }

    public function getHideAttribute()
    {
        return $this->visivel ? 'true' : 'none';
    }

    public function getTextoPadraoAttribute()
    {
        return $this->valor_padrao;
    }

    public function getAddClassAttribute()
    {
        return $this->classe_css;
    }

    public function getInputFocuAttribute()
    {
        return $this->eventos_frontend['focus'] ?? null;
    }

    public function getInputLoadAttribute()
    {
        return $this->eventos_frontend['load'] ?? null;
    }

    public function getInputBlurAttribute()
    {
        return $this->eventos_frontend['blur'] ?? null;
    }

    public function getPlaceholderAttribute()
    {
        return '@campo' . $this->id_input . '@';
    }

    public function getSelectOptionsAttribute()
    {
        return $this->opcoes->map(function ($item) {
            return [
                'label' => $item->rotulo,
                'return' => $item->valor_retorno ?: $item->rotulo,
            ];
        })->values()->all();
    }

    public function getDadosAttribute()
    {
        return $this->opcoes;
    }
}
