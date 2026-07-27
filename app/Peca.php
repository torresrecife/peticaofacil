<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class Peca extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_pecas_tb';
    protected $primaryKey = 'id_pecas';
    public $timestamps = false;

    protected $fillable = [
        'tipo_id',
        'id_usu',
        'nome_pecas',
        'nome_cli',
        'cod_pecas',
        'data_cad',
        'cod_sav',
    ];

    protected $legacyUtf8Fields = [
        'nome_pecas',
        'nome_cli',
        'cod_pecas',
    ];

    protected $casts = [
        'data_cad' => 'datetime',
    ];

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'tipo_id', 'tipo_id');
    }

    public function modeloNormalizado()
    {
        return $this->belongsTo(PeticaoModelo::class, 'tipo_id', 'legacy_tipo_id');
    }

    public function legacyUsuario()
    {
        return $this->belongsTo(LegacyUser::class, 'id_usu', 'id_usu');
    }
}
