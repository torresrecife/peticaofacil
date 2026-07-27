<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class SqlServerConfig extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_config_db';
    protected $primaryKey = 'id_db';
    public $timestamps = false;

    protected $fillable = [
        'nome_db',
        'ip_db',
        'data_db',
        'usu_db',
        'senha_db',
        'table_db',
        'chave_db',
        'query_db',
        'where_db',
        'stt',
    ];

    protected $legacyUtf8Fields = [
        'nome_db',
        'ip_db',
        'data_db',
        'usu_db',
        'senha_db',
        'table_db',
        'chave_db',
        'query_db',
        'where_db',
    ];

    public function scopeActive($query)
    {
        return $query->where('stt', 'Y');
    }
}
