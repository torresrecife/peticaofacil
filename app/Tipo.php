<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class Tipo extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_tipo_tb';
    protected $primaryKey = 'tipo_id';
    public $timestamps = false;

    protected $fillable = [
        'id_db',
        'nome_pre',
        'nome_pos',
        'tipo_nome',
        'id_cliente',
        'tipo_data',
        'tipo_stt',
        'id_setor',
        'cod_cabec',
        'cod_rodap',
        'tipo_arq',
    ];

    protected $legacyUtf8Fields = [
        'nome_pre',
        'nome_pos',
        'tipo_nome',
        'cod_cabec',
        'cod_rodap',
    ];

    protected $casts = [
        'tipo_data' => 'datetime',
    ];

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'id_setor', 'id_setor');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'cliente_id');
    }

    public function servidor()
    {
        return $this->belongsTo(SqlServerConfig::class, 'id_db', 'id_db');
    }

    public function paragrafos()
    {
        return $this->hasMany(Paragrafo::class, 'tipo_id', 'tipo_id')->orderBy('fund_order');
    }

    public function campos()
    {
        return $this->hasMany(InputCampo::class, 'tipo_id', 'tipo_id')
            ->where('listsel', 'N')
            ->orderBy('input_order')
            ->orderBy('id_input');
    }
}
