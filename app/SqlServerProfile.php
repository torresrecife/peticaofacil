<?php

namespace App;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SqlServerProfile extends Model
{
    protected $table = 'sql_server_profiles';

    protected $fillable = [
        'legacy_config_id',
        'nome',
        'host',
        'database_name',
        'username',
        'password',
        'table_name',
        'lookup_key',
        'base_query',
        'where_clause',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * A senha fica criptografada no banco com a chave da aplicacao.
     * Um valor que nao decifra e tratado como texto puro: e o caso de
     * registros anteriores a migration de criptografia.
     */
    public function getPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $exception) {
            return $value;
        }
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = ($value === null || $value === '')
            ? null
            : Crypt::encryptString((string) $value);
    }

    public function hasPassword()
    {
        return !empty($this->attributes['password']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    public function getIdDbAttribute()
    {
        return $this->legacy_config_id;
    }

    public function getNomeDbAttribute()
    {
        return $this->nome;
    }

    public function getIpDbAttribute()
    {
        return $this->host;
    }

    public function getDataDbAttribute()
    {
        return $this->database_name;
    }

    public function getUsuDbAttribute()
    {
        return $this->username;
    }

    public function getSenhaDbAttribute()
    {
        return $this->password;
    }

    public function getTableDbAttribute()
    {
        return $this->table_name;
    }

    public function getChaveDbAttribute()
    {
        return $this->lookup_key;
    }

    public function getQueryDbAttribute()
    {
        return $this->base_query;
    }

    public function getWhereDbAttribute()
    {
        return $this->where_clause;
    }

    public function getSttAttribute()
    {
        return $this->status === 'ativo' ? 'Y' : 'N';
    }
}
