<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class InputDado extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_dados_tb';
    protected $primaryKey = 'id_dados';
    public $timestamps = false;

    protected $fillable = [
        'id_input',
        'nome_dados',
        'return_1',
        'return_2',
        'return_3',
        'return_4',
        'return_5',
        'data_cad',
        'dados_order',
        'id_setor',
        'listsel',
    ];

    protected $legacyUtf8Fields = [
        'nome_dados',
        'return_1',
        'return_2',
        'return_3',
        'return_4',
        'return_5',
    ];
}
