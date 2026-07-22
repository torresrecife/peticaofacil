<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoNormalizada extends Model
{
    protected $table = 'peticoes';

    protected $fillable = [
        'legacy_peca_id',
        'modelo_id',
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

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }
}
