<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoVersao extends Model
{
    protected $table = 'peticao_versoes';

    protected $fillable = [
        'peticao_id',
        'versao_numero',
        'legacy_peca_id_snapshot',
        'legacy_usuario_id_snapshot',
        'codigo_externo_snapshot',
        'cliente_referencia_snapshot',
        'conteudo_html_snapshot',
        'campos_resolvidos_snapshot',
        'origem_snapshot',
        'criado_em',
    ];

    protected $casts = [
        'campos_resolvidos_snapshot' => 'array',
        'criado_em' => 'datetime',
    ];

    public function peticao()
    {
        return $this->belongsTo(PeticaoNormalizada::class, 'peticao_id');
    }

    public function legacyUsuario()
    {
        return $this->belongsTo(User::class, 'legacy_usuario_id_snapshot', 'id_usu');
    }
}
