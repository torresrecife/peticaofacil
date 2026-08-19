<?php

namespace App;

use App\Support\DatabaseEncoding;
use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
    use DatabaseEncoding;

    protected $table = 'setores';
    protected $primaryKey = 'id_setor';
    public $timestamps = false;

    protected $fillable = [
        'nome_setor',
        'data_cad',
        'cod_setor',
    ];

    protected $databaseEncodedFields = [
        'nome_setor',
        'cod_setor',
    ];

    protected $casts = [
        'data_cad' => 'datetime',
    ];
}
