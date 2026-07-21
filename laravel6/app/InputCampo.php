<?php

namespace App;

use App\Support\LegacyEncoding;
use Illuminate\Database\Eloquent\Model;

class InputCampo extends Model
{
    use LegacyEncoding;

    protected $table = 'tp_inputs_tb';
    protected $primaryKey = 'id_input';
    public $timestamps = false;

    protected $fillable = [
        'tipo_id',
        'input_pre',
        'input_pos',
        'input_title',
        'input_tipo',
        'input_db',
        'input_val',
        'input_alt',
        'input_ext',
        'input_cols',
        'input_rols',
        'input_focu',
        'input_load',
        'input_blur',
        'input_width',
        'input_req',
        'input_order',
        'listsel',
        'nomepet',
        'hide',
        'texto_padrao',
        'add_class',
    ];

    protected $legacyUtf8Fields = [
        'input_pre',
        'input_pos',
        'input_title',
        'input_db',
        'input_val',
        'input_alt',
        'input_focu',
        'input_load',
        'input_blur',
        'texto_padrao',
        'add_class',
    ];

    public function dados()
    {
        return $this->hasMany(InputDado::class, 'id_input', 'id_input')
            ->where('listsel', 'N')
            ->orderBy('dados_order')
            ->orderBy('id_dados');
    }

    public function getPlaceholderAttribute()
    {
        return '@campo' . $this->id_input . '@';
    }

    public function getSelectOptionsAttribute()
    {
        return $this->dados->map(function ($item) {
            return [
                'label' => $item->nome_dados,
                'return' => $item->return_1 ?: $item->nome_dados,
            ];
        })->values()->all();
    }
}
