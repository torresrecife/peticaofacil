<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PeticaoModeloCampo extends Model
{
    protected $table = 'peticao_modelo_campos';

    protected $fillable = [
        'modelo_id',
        'legacy_input_id',
        'rotulo',
        'token',
        'tipo',
        'comportamento',
        'origem_coluna',
        'origem_alias',
        'prefixo',
        'sufixo',
        'valor_padrao',
        'classe_css',
        'largura',
        'colunas_layout',
        'linhas_layout',
        'ordem',
        'obrigatorio',
        'visivel',
        'gera_nome_arquivo',
        'eventos_frontend',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'visivel' => 'boolean',
        'gera_nome_arquivo' => 'boolean',
        'eventos_frontend' => 'array',
    ];

    public function modelo()
    {
        return $this->belongsTo(PeticaoModelo::class, 'modelo_id');
    }

    public function opcoes()
    {
        return $this->hasMany(PeticaoModeloCampoOpcao::class, 'campo_id')->orderBy('ordem');
    }

    public function getIdInputAttribute()
    {
        return $this->legacy_input_id ?: $this->id;
    }

    public function getInputTitleAttribute()
    {
        return $this->rotulo;
    }

    public function getInputTipoAttribute()
    {
        return $this->tipo;
    }

    public function getInputValAttribute()
    {
        return $this->origem_coluna;
    }

    public function getInputDbAttribute()
    {
        return $this->origem_alias;
    }

    public function getInputPreAttribute()
    {
        return $this->prefixo;
    }

    public function getInputPosAttribute()
    {
        return $this->sufixo;
    }

    public function getInputColsAttribute()
    {
        return $this->colunas_layout ?: 1;
    }

    public function getInputRolsAttribute()
    {
        return $this->linhas_layout ?: 0;
    }

    public function getInputWidthAttribute()
    {
        return $this->largura;
    }

    public function getInputReqAttribute()
    {
        return $this->obrigatorio ? '1' : '0';
    }

    public function getInputOrderAttribute()
    {
        return $this->ordem;
    }

    public function getNomepetAttribute()
    {
        return $this->gera_nome_arquivo ? 'Y' : 'N';
    }

    public function getHideAttribute()
    {
        return $this->visivel ? 'true' : 'none';
    }

    public function getTextoPadraoAttribute()
    {
        return $this->valor_padrao;
    }

    public function getAddClassAttribute()
    {
        return $this->classe_css;
    }

    public function getInputFocuAttribute()
    {
        return $this->eventos_frontend['focus'] ?? null;
    }

    public function getInputLoadAttribute()
    {
        return $this->eventos_frontend['load'] ?? null;
    }

    public function getInputBlurAttribute()
    {
        return $this->eventos_frontend['blur'] ?? null;
    }

    public function getInputBehaviorAttribute()
    {
        if (!empty($this->attributes['comportamento'])) {
            return $this->normalizeBehaviorValue($this->attributes['comportamento']);
        }

        foreach (['focus', 'load', 'blur'] as $eventName) {
            if ($this->detectEventPreset($this->eventos_frontend[$eventName] ?? null)) {
                return 'date';
            }
        }

        return '';
    }

    public function getInputAltAttribute()
    {
        return $this->input_behavior;
    }

    public function getInputFocuPresetAttribute()
    {
        return $this->detectEventPreset($this->input_focu);
    }

    public function getInputLoadPresetAttribute()
    {
        return $this->detectEventPreset($this->input_load);
    }

    public function getInputBlurPresetAttribute()
    {
        return $this->detectEventPreset($this->input_blur);
    }

    public function getPlaceholderAttribute()
    {
        return '@campo' . $this->id_input . '@';
    }

    public function getSelectOptionsAttribute()
    {
        if ($this->hasAssociatedListSource()) {
            return $this->buildListSelectOptions();
        }

        return $this->opcoes->map(function ($item) {
            return [
                'id' => $item->id_dados,
                'label' => $item->rotulo,
                'value' => $item->rotulo,
                'return' => $item->valor_retorno ?: $item->rotulo,
                'extras' => array_filter(array_merge(
                    ['return_1' => $item->valor_retorno ?: $item->rotulo],
                    $item->valores_extras ?: []
                ), function ($value) {
                    return $value !== null && $value !== '';
                }),
            ];
        })->values()->all();
    }

    public function getDadosAttribute()
    {
        return $this->opcoes;
    }

    public function hasAssociatedListSource()
    {
        return !empty($this->getAssociatedListConfigAttribute());
    }

    public function getAssociatedListConfigAttribute()
    {
        $raw = trim((string) $this->input_db);
        if ($raw === '' || !Str::startsWith($raw, 'tp_lista_tb_|_')) {
            return null;
        }

        $parts = explode('_|_', $raw);
        $labelColumn = $parts[1] ?? 'nome_lista';
        $returnColumn = $parts[2] ?? 'return_1';
        $filter = $parts[3] ?? '';
        $layout = $parts[4] ?? null;

        $groupId = null;
        if (preg_match('/id_grupo=(\d+)/i', $filter, $matches)) {
            $groupId = (int) $matches[1];
        }

        if (!$groupId) {
            return null;
        }

        return [
            'source_table' => 'tp_lista_tb',
            'group_id' => $groupId,
            'label_column' => $labelColumn ?: 'nome_lista',
            'return_column' => $returnColumn ?: 'return_1',
            'filter' => $filter,
            'layout' => $layout,
        ];
    }

    public function getDependentFillConfigAttribute()
    {
        foreach (['focus', 'load', 'blur'] as $eventName) {
            $script = trim((string) ($this->eventos_frontend[$eventName] ?? ''));
            if ($script === '') {
                continue;
            }

            if (preg_match('/fc_ajax_comp\("([^"]+)","([^"]+)","campo(\d+)","[^"]+","([^"]+)",this,1\)/i', $script, $matches)) {
                return [
                    'source_table' => $matches[1],
                    'return_column' => $matches[2],
                    'target_field_id' => (int) $matches[3],
                    'lookup_key' => $matches[4],
                    'event' => $eventName,
                    'raw' => $script,
                ];
            }
        }

        return null;
    }

    protected function detectEventPreset($script)
    {
        $script = trim((string) $script);
        if ($script === '') {
            return '';
        }

        if (stripos($script, 'data_extenso_out(this)') !== false) {
            return 'data_extenso_out';
        }

        if (stripos($script, 'data_atual(this)') !== false) {
            return 'data_atual';
        }

        if (stripos($script, 'dia_semana(this)') !== false) {
            return 'dia_semana';
        }

        return '';
    }

    protected function normalizeBehaviorValue($value)
    {
        $value = strtolower(trim((string) $value));

        $map = [
            '' => '',
            'padrao' => '',
            'default' => '',
            'date' => 'date',
            'data' => 'date',
            'decimal' => 'decimal',
            'valor' => 'decimal',
            'cpf' => 'cpf',
            'cnpj' => 'cnpj',
            'fone' => 'fone',
            'phone' => 'fone',
            'cep' => 'cep',
            'integer' => 'integer',
            'numero' => 'integer',
            'número' => 'integer',
        ];

        return $map[$value] ?? '';
    }

    protected function buildListSelectOptions()
    {
        $config = $this->associated_list_config;
        if (!$config) {
            return [];
        }

        return ListaItem::query()
            ->where('id_grupo', $config['group_id'])
            ->orderBy('nome_lista')
            ->get()
            ->map(function ($item) use ($config) {
                $extras = [];
                foreach (['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $column) {
                    $value = $item->{$column};
                    if ($value !== null && $value !== '') {
                        $extras[$column] = $value;
                    }
                }

                $labelColumn = $config['label_column'];
                $label = (string) ($item->{$labelColumn} ?? $item->nome_lista);
                $returnColumn = $config['return_column'];

                return [
                    'id' => $item->legacy_lista_id ?: $item->id_lista,
                    'label' => $label,
                    'value' => $label,
                    'return' => (string) ($item->{$returnColumn} ?? $label),
                    'extras' => $extras,
                ];
            })
            ->values()
            ->all();
    }
}
