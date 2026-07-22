<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PeticaoModeloParagrafo extends Model
{
    protected $table = 'peticao_modelo_paragrafos';

    protected $fillable = [
        'modelo_id',
        'legacy_fund_id',
        'titulo',
        'conteudo_html',
        'ordem',
        'visivel',
        'ativo',
    ];

    protected $casts = [
        'visivel' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function getFundTextAttribute()
    {
        return $this->conteudo_html;
    }

    public function getFundIdAttribute()
    {
        return $this->legacy_fund_id ?: $this->id;
    }

    public function getFundTituloAttribute()
    {
        return $this->titulo;
    }

    public function getFundOrderAttribute()
    {
        return $this->ordem;
    }
}
