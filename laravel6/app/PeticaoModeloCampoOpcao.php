<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoModeloCampoOpcao extends Model
{
    protected $table = 'peticao_modelo_campo_opcoes';

    protected $fillable = [
        'campo_id',
        'legacy_dado_id',
        'rotulo',
        'valor_retorno',
        'valores_extras',
        'ordem',
    ];

    protected $casts = [
        'valores_extras' => 'array',
    ];

    public function campo()
    {
        return $this->belongsTo(PeticaoModeloCampo::class, 'campo_id');
    }
}
