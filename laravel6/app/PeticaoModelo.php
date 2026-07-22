<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoModelo extends Model
{
    protected $table = 'peticao_modelos';

    protected $fillable = [
        'legacy_tipo_id',
        'legacy_cliente_id',
        'legacy_setor_id',
        'legacy_sql_config_id',
        'nome',
        'slug',
        'status',
        'arquivo_padrao',
        'cabecalho_html',
        'rodape_html',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function paragrafos()
    {
        return $this->hasMany(PeticaoModeloParagrafo::class, 'modelo_id')->orderBy('ordem');
    }

    public function campos()
    {
        return $this->hasMany(PeticaoModeloCampo::class, 'modelo_id')->orderBy('ordem');
    }
}
