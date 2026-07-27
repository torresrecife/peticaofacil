<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class ListaGrupo extends Model
{
    use LegacyEncoding;

    protected $table = 'lista_grupos';
    protected $primaryKey = 'id_grupo';
    public $incrementing = false;

    protected $fillable = [
        'id_grupo',
        'legacy_grupo_id',
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

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $grupo = $this->where($field, $value)->first();
        if ($grupo) {
            return $grupo;
        }

        $legacyGrupo = LegacyListaGrupo::find($value);
        if (!$legacyGrupo) {
            return null;
        }

        return app(\App\Services\ListaSyncService::class)->syncLegacyGroup($legacyGrupo);
    }
}
