<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFavoriteModelo extends Model
{
    protected $table = 'user_model_favorites';

    protected $fillable = [
        'user_id',
        'legacy_usuario_id',
        'source',
        'modelo_id',
        'legacy_tipo_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class, 'legacy_tipo_id', 'tipo_id');
    }
}
