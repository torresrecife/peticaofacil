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

    public function getIdDadosAttribute()
    {
        return $this->legacy_dado_id ?: $this->id;
    }

    public function getNomeDadosAttribute()
    {
        return $this->rotulo;
    }

    /**
     * Um retorno "vazio de verdade": alem de null e "", trata as strings
     * literais "undefined" e "null" que vieram do sistema legado (onde o
     * paragrafo predefinido nunca foi preenchido e ficou gravado assim).
     */
    public static function normalizeReturn($value)
    {
        $value = trim((string) $value);
        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);

        return ($value === '' || in_array($lower, ['undefined', 'null'], true)) ? null : $value;
    }

    public function getValorRetornoAttribute($value)
    {
        return static::normalizeReturn($value);
    }

    public function getReturn1Attribute()
    {
        return $this->valor_retorno;
    }

    public function getDadosOrderAttribute()
    {
        return $this->ordem;
    }
}
