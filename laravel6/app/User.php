<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
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

    protected $hidden = [
        'senha_usu',
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

    public function getAuthPassword()
    {
        return $this->senha_usu;
    }

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'id_setor', 'id_setor');
    }

    public function scopeActive($query)
    {
        return $query->where('status_usu', 'ATI');
    }

    public function isAdmin()
    {
        return in_array($this->nivel_usu, ['ADM', 'GER'], true);
    }

    public function requiresInitialPasswordChange()
    {
        $access = $this->getOriginal('acesso_usu');

        return $access === null
            || $access === ''
            || $access === '0000-00-00 00:00:00';
    }

    public function getClientIdsAttribute()
    {
        if (!$this->id_cliente || $this->id_cliente === '0') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->id_cliente))));
    }
}
