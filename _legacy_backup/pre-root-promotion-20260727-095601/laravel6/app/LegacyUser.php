<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class LegacyUser extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_usu_tb';
    protected $primaryKey = 'id_usu';
    public $timestamps = false;

    protected $fillable = [
        'nome_usu',
        'login_usu',
        'senha_usu',
        'email_usu',
        'nivel_usu',
        'acesso_usu',
        'data_cad',
        'id_setor',
        'id_cliente',
        'status_usu',
        'estados_usu',
        'comarca_usu',
    ];

    protected $legacyUtf8Fields = [
        'nome_usu',
        'login_usu',
        'email_usu',
        'estados_usu',
        'comarca_usu',
    ];

    protected $casts = [
        'acesso_usu' => 'datetime',
        'data_cad' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status_usu', 'ATI');
    }
}
