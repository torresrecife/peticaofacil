<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_clientes_db';
    protected $primaryKey = 'cliente_id';
    public $timestamps = false;

    protected $fillable = [
        'cliente_name',
        'cliente_cod',
        'cliente_creator',
        'cliente_area',
        'cliente_status',
    ];

    protected $legacyUtf8Fields = [
        'cliente_name',
        'cliente_cod',
    ];

    protected $casts = [
        'cliente_creator' => 'datetime',
    ];

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'cliente_area', 'id_setor');
    }

    public function scopeActive($query)
    {
        return $query->where('cliente_status', 'Y');
    }
}
