<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class Paragrafo extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_funda_tb';
    protected $primaryKey = 'fund_id';
    public $timestamps = false;

    protected $fillable = [
        'tipo_id',
        'fund_titulo',
        'fund_text',
        'fund_order',
        'fund_data',
        'fund_visi',
        'fund_stt',
    ];

    protected $legacyUtf8Fields = [
        'fund_titulo',
        'fund_text',
    ];
}
