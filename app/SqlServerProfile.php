<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
