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

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'legacy_setor_id', 'id_setor');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'legacy_cliente_id', 'cliente_id');
    }

    public function servidor()
    {
        return $this->belongsTo(SqlServerProfile::class, 'legacy_sql_config_id', 'legacy_config_id');
    }

    public function servidorLegacy()
    {
        return $this->belongsTo(SqlServerConfig::class, 'legacy_sql_config_id', 'id_db');
    }

    public function paragrafos()
    {
        return $this->hasMany(PeticaoModeloParagrafo::class, 'modelo_id')->orderBy('ordem');
    }

    public function campos()
    {
        return $this->hasMany(PeticaoModeloCampo::class, 'modelo_id')->orderBy('ordem');
    }

    public function getTipoIdAttribute()
    {
        return $this->legacy_tipo_id ?: $this->id;
    }

    public function getTipoNomeAttribute()
    {
        return $this->nome;
    }

    public function getNomePreAttribute()
    {
        return $this->metadata['nome_pre'] ?? null;
    }

    public function getNomePosAttribute()
    {
        return $this->metadata['nome_pos'] ?? null;
    }

    public function getCodCabecAttribute()
    {
        return $this->cabecalho_html;
    }

    public function getCodRodapAttribute()
    {
        return $this->rodape_html;
    }

    public function getTipoSttAttribute()
    {
        return $this->status === 'ativo' ? 'Y' : 'N';
    }

    public function getTipoArqAttribute()
    {
        return $this->arquivo_padrao;
    }

    public function getIdDbAttribute()
    {
        return $this->legacy_sql_config_id;
    }

    public function getIdClienteAttribute()
    {
        return $this->legacy_cliente_id;
    }

    public function getIdSetorAttribute()
    {
        return $this->legacy_setor_id;
    }
}
