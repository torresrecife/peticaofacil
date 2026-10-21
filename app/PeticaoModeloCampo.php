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

    public function getConditionalConfigAttribute()
    {
        $config = $this->eventos_frontend['conditional'] ?? null;

        return is_array($config) ? $config : null;
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
            // valor_retorno ja chega normalizado (null para "undefined"/"null"/vazio).
            $retorno = $item->valor_retorno;

            // Extras expostos como data-return-* para o preenchimento dependente.
            // return_1 e o retorno real (o paragrafo); sem ele, o campo alvo fica
            // vazio para o usuario digitar, em vez de receber "undefined" ou o rotulo.
            $extras = [];
            foreach (array_merge(['return_1' => $retorno], $item->valores_extras ?: []) as $key => $value) {
                $value = PeticaoModeloCampoOpcao::normalizeReturn($value);
                if ($value !== null) {
                    $extras[$key] = $value;
                }
            }

            return [
                'id' => $item->id_dados,
                'label' => $item->rotulo,
                'value' => $item->rotulo,
                'return' => $retorno ?: $item->rotulo,
                'extras' => $extras,
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
        $layout = strtolower(trim((string) ($parts[4] ?? '')));

        // 6o campo do input_db = campo do qual esta lista depende (ex.: ESTADO).
        // No legado a lista "hori" e filtrada por nome_lista = valor desse campo.
        $dependencyFieldId = null;
        if (isset($parts[5]) && preg_match('/(\d+)/', $parts[5], $dm)) {
            $dependencyFieldId = (int) $dm[1];
        }

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
            'return_columns' => array_values(array_filter(preg_split('/\s*[+,;|]\s*/', $returnColumn ?: 'return_1'))),
            'filter' => $filter,
            'layout' => $layout !== '' ? $layout : null,
            'dependency_field_id' => $dependencyFieldId,
        ];
    }

    public function getDependentFillConfigAttribute()
    {
        $configs = $this->dependent_fill_configs;

        return $configs ? $configs[0] : null;
    }

    /**
     * Todos os fc_ajax_comp do campo. Um mesmo select pode abastecer mais de um
     * alvo (ex.: SEGREDO DE JUSTICA: return_1 no titulo e return_2 no paragrafo).
     */
    public function getDependentFillConfigsAttribute()
    {
        $configs = [];

        foreach (['focus', 'load', 'blur'] as $eventName) {
            $script = trim((string) ($this->eventos_frontend[$eventName] ?? ''));
            if ($script === '') {
                continue;
            }

            if (preg_match_all('/fc_ajax_comp\("([^"]+)","([^"]+)","campo(\d+)","[^"]+","([^"]+)",this,1\)/i', $script, $all, PREG_SET_ORDER)) {
                foreach ($all as $matches) {
                    $key = (int) $matches[3] . '|' . $matches[2];
                    if (isset($configs[$key])) {
                        continue;
                    }

                    $configs[$key] = [
                        'source_table' => $matches[1],
                        'return_column' => $matches[2],
                        'target_field_id' => (int) $matches[3],
                        'lookup_key' => $matches[4],
                        'event' => $eventName,
                        'raw' => $script,
                    ];
                }
            }
        }

        return array_values($configs);
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

        if (stripos($script, 'dia_semana(this)') !== false || stripos($script, 'diasemana(this)') !== false) {
            return 'dia_semana';
        }

        if (stripos($script, 'fc_newstring(this)') !== false) {
            return 'valor_extenso';
        }

        return '';
    }

    protected function normalizeBehaviorValue($value)
    {
        return static::mapLegacyBehavior($value);
    }

    /**
     * Mapeia o input_alt do sistema antigo (e os rotulos de comportamento do
     * novo) para o conjunto de mascaras suportado pela montagem. Publica e
     * estatica para que o backfill possa reutilizar a mesma tabela.
     */
    public static function mapLegacyBehavior($value)
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
            'cpf_cnpj' => 'cpf_cnpj',
            'cpfcnpj' => 'cpf_cnpj',
            'fone' => 'fone',
            'phone' => 'fone',
            'cep' => 'cep',
            'integer' => 'integer',
            'processo' => 'processo',
            'numero' => 'integer',
            'número' => 'integer',
        ];

        return $map[$value] ?? '';
    }

    /**
     * No sistema antigo tp_inputs_tb.input_req = 2 e obrigatorio e 1 nao e
     * (tela de cadastro: Sim=2, Nao=1; EnviarDados() barra obrigatorio==2).
     */
    public static function mapLegacyRequired($inputReq)
    {
        return (int) trim((string) $inputReq) === 2;
    }

    protected function buildListSelectOptions()
    {
        $config = $this->associated_list_config;
        if (!$config) {
            return [];
        }

        $isHori = ($config['layout'] ?? null) === 'hori';
        $orderColumn = $isHori ? 'return_2' : ($config['label_column'] ?: 'nome_lista');

        return ListaItem::query()
            ->where('id_grupo', $config['group_id'])
            ->orderBy($orderColumn)
            ->get()
            ->map(function ($item) use ($config, $isHori) {
                $extras = [];
                foreach (['return_1', 'return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $column) {
                    $value = $item->{$column};
                    if ($value !== null && $value !== '') {
                        $extras[$column] = $value;
                    }
                }

                if ($isHori) {
                    // Lista dependente (ex.: FIEL DEPOSITARIO por ESTADO):
                    // - chave de dependencia = nome_lista (a sigla do estado);
                    // - rotulo exibido = nome (return_2);
                    // - valor inserido na peticao = clausula completa, concatenando
                    //   return_2..return_6 direto (como o "hori" do sistema antigo).
                    $clause = '';
                    foreach (['return_2', 'return_3', 'return_4', 'return_5', 'return_6'] as $c) {
                        $clause .= (string) ($item->{$c} ?? '');
                    }
                    $clause = trim($clause);
                    $label = trim((string) ($item->return_2 ?? ''));
                    if ($label === '') {
                        $label = trim((string) $item->nome_lista);
                    }

                    return [
                        'id' => $item->legacy_lista_id ?: $item->id_lista,
                        'label' => $label !== '' ? $label : '(sem nome)',
                        'value' => $clause !== '' ? $clause : $label,
                        'return' => $clause,
                        'dependency_key' => trim((string) $item->nome_lista),
                        'extras' => $extras,
                    ];
                }

                $labelColumn = $config['label_column'];
                $label = trim((string) ($item->{$labelColumn} ?? $item->nome_lista));
                $returnColumns = $config['return_columns'] ?: [$config['return_column']];
                $returnValues = [];
                foreach ($returnColumns as $returnColumn) {
                    $value = trim((string) ($item->{$returnColumn} ?? ''));
                    if ($value !== '') {
                        $returnValues[] = $value;
                    }
                }
                $resolved = $returnValues ? implode(' ', $returnValues) : $label;

                // Em lista "vert" o valor do SELECT continua sendo o rotulo (ex.: o
                // nome que vai para @campoN@); o(s) RETURN alimentam outro campo via
                // preenchimento dependente (fc_ajax_comp), nao o proprio SELECT.
                return [
                    'id' => $item->legacy_lista_id ?: $item->id_lista,
                    'label' => $label !== '' ? $label : '(sem nome)',
                    'value' => $label,
                    'return' => $resolved,
                    'dependency_key' => null,
                    'extras' => $extras,
                ];
            })
            ->values()
            ->all();
    }
}
