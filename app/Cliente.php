<?php

namespace App;

use App\Support\DatabaseEncoding;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use DatabaseEncoding;

    protected $table = 'clientes';
    protected $primaryKey = 'cliente_id';
    public $timestamps = false;

    protected $fillable = [
        'cliente_name',
        'cliente_cod',
        'cliente_creator',
        'cliente_area',
        'cliente_status',
    ];

    protected $databaseEncodedFields = [
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
