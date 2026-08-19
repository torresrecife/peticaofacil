<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
    use LegacyEncoding;

    protected $table = 'setores';
    protected $primaryKey = 'id_setor';
    public $timestamps = false;

    protected $fillable = [
        'nome_setor',
        'data_cad',
        'cod_setor',
    ];

    protected $legacyUtf8Fields = [
        'nome_setor',
        'cod_setor',
    ];

    protected $casts = [
        'data_cad' => 'datetime',
    ];
}
