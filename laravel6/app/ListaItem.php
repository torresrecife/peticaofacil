<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class ListaItem extends Model
{
    use LegacyEncoding;

    protected $table = 'lista_itens';
    protected $primaryKey = 'id_lista';
    public $incrementing = false;

    protected $fillable = [
        'id_lista',
        'legacy_lista_id',
        'id_grupo',
        'nome_lista',
        'return_1',
        'return_2',
        'return_3',
        'return_4',
        'return_5',
        'return_6',
        'id_setor',
        'data_cad',
    ];

    protected $legacyUtf8Fields = [
        'nome_lista',
        'return_1',
        'return_2',
        'return_3',
        'return_4',
        'return_5',
        'return_6',
    ];

    public function grupo()
    {
        return $this->belongsTo(ListaGrupo::class, 'id_grupo', 'id_grupo');
    }
}
