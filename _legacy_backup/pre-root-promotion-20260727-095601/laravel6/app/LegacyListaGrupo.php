<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class LegacyListaGrupo extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_grupo_tb';
    protected $primaryKey = 'id_grupo';
    public $timestamps = false;

    protected $fillable = [
        'nome_grupo',
        'data_cad',
    ];

    protected $legacyUtf8Fields = [
        'nome_grupo',
    ];

    public function itens()
    {
        return $this->hasMany(LegacyListaItem::class, 'id_grupo', 'id_grupo');
    }
}
