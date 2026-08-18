<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoNormalizada extends Model
{
    protected $table = 'peticoes';

    protected $fillable = [
        'legacy_peca_id',
        'modelo_id',
        'user_id',
        'legacy_usuario_id',
        'codigo_externo',
        'nome_arquivo',
        'cliente_referencia',
        'conteudo_html',
        'campos_resolvidos',
        'gerado_em',
        'salvo_em',
    ];

    protected $casts = [
        'campos_resolvidos' => 'array',
        'gerado_em' => 'datetime',
        'salvo_em' => 'datetime',
    ];

    public function getDisplayTitleAttribute()
    {
        if (data_get($this->campos_resolvidos, 'origem') === 'avulsa' && !empty($this->nome_arquivo)) {
            return $this->nome_arquivo;
        }

        if ($this->relationLoaded('modelo') && $this->modelo) {
            return $this->modelo->nome;
        }

        if ($this->modelo) {
            return $this->modelo->nome;
        }

        return $this->nome_arquivo ?: 'Peticao avulsa';
    }

    public function getDisplayReferenceAttribute()
    {
        if (!empty($this->cliente_referencia)) {
            return $this->cliente_referencia;
        }

        return data_get($this->campos_resolvidos, 'parte_contraria', '-');
    }

    public function getOriginLabelAttribute()
    {
        if (data_get($this->campos_resolvidos, 'origem') === 'avulsa') {
            return 'Avulsa';
        }

        return 'Modelo';
    }

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versoes()
    {
        return $this->hasMany(PeticaoVersao::class, 'peticao_id')->orderByDesc('versao_numero');
    }
}
