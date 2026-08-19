<?php

namespace App;

use App\Support\DatabaseEncoding;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use DatabaseEncoding;

    protected $table = 'users';

    protected $fillable = [
        'legacy_usuario_id',
        'name',
        'email',
        'password',
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
        'password',
        'remember_token',
    ];

    protected $databaseEncodedFields = [
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
        return $this->password;
    }

    public function getIdUsuAttribute()
    {
        return $this->legacy_usuario_id;
    }

    public function setIdUsuAttribute($value)
    {
        $this->attributes['legacy_usuario_id'] = $value;
    }

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'id_setor', 'id_setor');
    }

    public function favoriteModelos(): HasMany
    {
        return $this->hasMany(UserFavoriteModelo::class, 'user_id');
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
