<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class ListaGrupo extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_grupo_tb';
    protected $primaryKey = 'id_grupo';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_grupo',
        'nome_grupo',
        'data_cad',
    ];

    protected $legacyUtf8Fields = [
        'nome_grupo',
    ];

    public function itens()
    {
        return $this->hasMany(ListaItem::class, 'id_grupo', 'id_grupo');
    }
}
