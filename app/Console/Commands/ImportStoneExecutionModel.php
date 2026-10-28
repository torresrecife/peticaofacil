<?php

namespace App\Console\Commands;

use App\Cliente;
use App\ListaGrupo;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\PeticaoModeloParagrafo;
use App\Services\PeticaoModeloPackageService;
use App\Setor;
use App\SqlServerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa um pacote de modelo gerado por PeticaoModeloPackageService.
 *
 * O pacote nao traz IDs: os campos sao citados por chave ({{field:chave}} e
 * {{field_id:chave}}) e cliente, setor, servidor e listas pelo nome. Tudo e
 * resolvido contra o banco deste ambiente antes de qualquer gravacao; se algo
 * nao puder ser resolvido sem ambiguidade, nada e importado.
 */
class ImportStoneExecutionModel extends Command
{
    const MODEL_ATTRIBUTES = ['nome', 'slug', 'arquivo_padrao', 'cabecalho_html', 'rodape_html', 'metadata'];

    const FIELD_ATTRIBUTES = [
        'rotulo', 'tipo', 'comportamento', 'origem_coluna', 'origem_alias', 'prefixo', 'sufixo',
        'valor_padrao', 'classe_css', 'largura', 'colunas_layout', 'linhas_layout', 'ordem',
        'obrigatorio', 'visivel', 'gera_nome_arquivo', 'eventos_frontend',
    ];

    protected $signature = 'peticao:modelo-stone-import
        {--path= : Caminho do pacote JSON a validar/importar}
        {--dry-run : Apenas valida o pacote e as dependências}
        {--activate : Ativa o modelo após a importação}';

    protected $description = 'Importa ou atualiza um modelo a partir de um pacote JSON (sem --path, usa o pacote do modelo Stone)';

    private $problems = [];

    private $warnings = [];

    public function handle()
    {
        $this->problems = [];
        $this->warnings = [];

        $path = $this->option('path') ?: database_path('data/stone-execucao-titulo-extrajudicial.json');
        if (!is_file($path)) {
            $this->error('Pacote não encontrado: ' . $path);
            return 1;
        }

        $package = json_decode(file_get_contents($path), true);
        if (!$this->hasValidStructure($package)) {
            $this->error('Pacote inválido.');
            return 1;
        }

        $version = (int) ($package['version'] ?? 1);
        $this->checkFieldKeys($package);
        if (!$this->problems) {
            $this->checkReferences($package, $version);
        }

        $dependencies = is_array($package['dependencies'] ?? null) ? $package['dependencies'] : [];
        $sector = $this->resolveSector($dependencies);
        $client = $this->resolveClient($dependencies, $sector);
        $server = $this->resolveServer($dependencies);
        $listIds = $this->resolveLists($package['fields']);

        if ($this->problems) {
            foreach ($this->problems as $problem) {
                $this->error($problem);
            }
            $this->error('Nada foi importado.');
            return 1;
        }

        $slug = $this->modelSlug($package);
        $existing = PeticaoModelo::where('slug', $slug)->first();

        $this->line('Modelo: ' . $package['model']['nome'] . ($existing ? ' (atualiza o modelo #' . $existing->id . ')' : ' (novo)'));
        $this->line('Cliente: ' . ($client ? $client->cliente_name : 'sem cliente vinculado'));
        $this->line('Setor: ' . ($sector ? $sector->nome_setor : 'sem setor vinculado'));
        $this->line('Servidor: ' . ($server ? $server->nome : 'sem servidor vinculado'));
        $this->line('Campos: ' . count($package['fields']) . '; parágrafos: ' . count($package['paragraphs']));
        foreach ($this->warnings as $warning) {
            $this->warn('Aviso: ' . $warning);
        }

        if ($this->option('dry-run')) {
            $this->info('Pacote validado. Nenhuma alteração foi gravada.');
            return 0;
        }

        $status = $this->option('activate') ? 'ativo' : 'inativo';

        DB::transaction(function () use ($package, $slug, $client, $sector, $server, $listIds, $status) {
            $this->store($package, $slug, $client, $sector, $server, $listIds, $status);
        });

        $this->info('Modelo "' . $package['model']['nome'] . '" importado com sucesso no status ' . $status . '.');
        return 0;
    }

    private function hasValidStructure($package)
    {
        if (!is_array($package) || !is_array($package['model'] ?? null)) {
            return false;
        }
        if (trim((string) ($package['model']['nome'] ?? '')) === '') {
            return false;
        }
        if (!is_array($package['fields'] ?? null) || !is_array($package['paragraphs'] ?? null)) {
            return false;
        }
        foreach (array_merge($package['fields'], $package['paragraphs']) as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return count($package['fields']) > 0 || count($package['paragraphs']) > 0;
    }

    private function modelSlug(array $package)
    {
        $slug = trim((string) ($package['model']['slug'] ?? ''));

        return $slug !== '' ? $slug : Str::slug($package['model']['nome']);
    }

    private function checkFieldKeys(array $package)
    {
        $keys = [];
        foreach ($package['fields'] as $index => $definition) {
            $key = $definition['key'] ?? null;
            if (!is_string($key) || trim($key) === '') {
                $this->problems[] = 'O campo nº ' . ($index + 1) . ' do pacote não tem chave ("key").';
                continue;
            }
            if (isset($keys[$key])) {
                $this->problems[] = 'O pacote contém a chave de campo "' . $key . '" mais de uma vez.';
            }
            $keys[$key] = true;
        }
    }

    /**
     * Toda citacao a um campo precisa ser uma referencia por chave. Um
     * marcador @campoN@ com o numero do ambiente de origem apontaria, aqui,
     * para um campo qualquer (ou nenhum) e a peticao sairia com o marcador
     * visivel no texto.
     */
    private function checkReferences(array $package, $version)
    {
        $keys = array_column($package['fields'], 'key');
        $json = json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        preg_match_all('/\{\{field(?:_id)?:([^}]+)\}\}/', $json, $references);
        $unknown = array_diff(array_unique($references[1]), $keys);
        if ($unknown) {
            $this->problems[] = 'Referências a campos que não estão no pacote: ' . implode(', ', $unknown) . '.';
        }

        preg_match_all('/@campo\d+@/i', $json, $raw);
        $raw = array_values(array_unique($raw[0]));
        if ($version >= PeticaoModeloPackageService::VERSION) {
            $raw = array_merge($raw, $this->foreignFieldsInEvents($package['fields']));
        }
        if (!$raw) {
            return;
        }

        $sample = implode(', ', array_slice($raw, 0, 8)) . (count($raw) > 8 ? '…' : '');
        if ($version < PeticaoModeloPackageService::VERSION) {
            $this->problems[] = 'Este pacote foi gerado por uma versão antiga da exportação e cita campos pelo número do '
                . 'ambiente de origem (' . $sample . '). Esses números não valem neste ambiente. '
                . 'Exporte o modelo novamente no ambiente de origem e importe o novo arquivo.';
            return;
        }

        $this->warnings[] = 'O pacote cita campos que não pertencem ao modelo (' . $sample . '); eles foram mantidos como estão.';
    }

    /**
     * Num pacote portavel, "campo123" que sobrou num evento e de um campo que
     * nao existe no modelo: ja nao funcionava na origem e segue sem funcionar.
     */
    private function foreignFieldsInEvents(array $fields)
    {
        $found = [];
        foreach ($fields as $definition) {
            $events = $definition['eventos_frontend'] ?? null;
            if (!is_array($events)) {
                continue;
            }
            array_walk_recursive($events, function ($value) use (&$found) {
                if (is_string($value) && preg_match_all('/(?<![A-Za-z0-9@])campo\d+(?![0-9@])/i', $value, $matches)) {
                    foreach ($matches[0] as $match) {
                        $found[$match] = true;
                    }
                }
            });
        }

        return array_keys($found);
    }

    private function resolveSector(array $dependencies)
    {
        $name = trim((string) ($dependencies['sector_name'] ?? ''));
        $code = trim((string) ($dependencies['sector_code'] ?? ''));
        if ($name === '' && $code === '') {
            $this->warnings[] = 'O pacote não informa setor; o modelo ficará sem setor vinculado.';
            return null;
        }

        $all = Setor::orderBy('id_setor')->get();
        $describe = function ($sectors) {
            return $sectors->map(function ($sector) {
                return '"' . $sector->nome_setor . '" (código ' . ($sector->cod_setor ?: '-') . ')';
            })->implode(', ');
        };
        $withCode = function ($sectors) use ($code) {
            return $sectors->filter(function ($sector) use ($code) {
                return $this->same($sector->cod_setor, $code);
            })->values();
        };

        if ($name !== '') {
            $matches = $all->filter(function ($sector) use ($name) {
                return $this->same($sector->nome_setor, $name);
            })->values();
            if ($matches->count() > 1 && $code !== '') {
                $matches = $withCode($matches);
            }
            if ($matches->count() === 1) {
                return $matches->first();
            }

            $this->problems[] = $matches->count() > 1
                ? 'Há mais de um setor chamado "' . $name . '" neste ambiente; não é possível escolher um deles com segurança.'
                : 'O setor "' . $name . '" não existe neste ambiente. Setores cadastrados: ' . ($describe($all) ?: 'nenhum') . '.';
            return null;
        }

        // Pacotes antigos so trazem o codigo, que nao e unico (ha setores diferentes com o mesmo codigo).
        $matches = $withCode($all);
        if ($matches->count() === 1) {
            return $matches->first();
        }

        $this->problems[] = $matches->count() > 1
            ? 'O pacote indica o setor apenas pelo código "' . $code . '", que neste ambiente pertence a mais de um setor: '
                . $describe($matches) . '. Exporte o modelo novamente (o novo pacote leva o nome do setor) '
                . 'ou informe "sector_name" em "dependencies".'
            : 'Nenhum setor com o código "' . $code . '" neste ambiente.';
        return null;
    }

    private function resolveClient(array $dependencies, $sector)
    {
        $name = trim((string) ($dependencies['client'] ?? ''));
        $code = trim((string) ($dependencies['client_code'] ?? ''));
        if ($name === '') {
            return null;
        }

        $matches = Cliente::orderBy('cliente_id')->get()->filter(function ($client) use ($name) {
            return $this->same($client->cliente_name, $name);
        })->values();

        if ($matches->count() > 1 && $code !== '') {
            $byCode = $matches->filter(function ($client) use ($code) {
                return $this->same($client->cliente_cod, $code);
            })->values();
            $matches = $byCode->count() ? $byCode : $matches;
        }
        if ($matches->count() > 1 && $sector) {
            $bySector = $matches->filter(function ($client) use ($sector) {
                return (int) $client->cliente_area === (int) $sector->id_setor;
            })->values();
            $matches = $bySector->count() ? $bySector : $matches;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() === 0) {
            $this->warnings[] = 'O cliente "' . $name . '" não existe neste ambiente; o modelo ficará sem cliente vinculado.';
            return null;
        }

        $this->problems[] = 'Há mais de um cliente chamado "' . $name . '" neste ambiente ('
            . $matches->map(function ($client) {
                return '#' . $client->cliente_id . ' código ' . ($client->cliente_cod ?: '-');
            })->implode(', ')
            . ') e o pacote não permite distinguir qual deles usar.';
        return null;
    }

    private function resolveServer(array $dependencies)
    {
        $name = trim((string) ($dependencies['server'] ?? ''));
        if ($name === '') {
            return null;
        }

        $matches = SqlServerProfile::orderBy('id')->get()->filter(function ($server) use ($name) {
            return $this->same($server->nome, $name);
        })->values();
        if ($matches->count() > 1) {
            $active = $matches->where('status', 'ativo')->values();
            $matches = $active->count() ? $active : $matches;
        }

        if ($matches->count() === 0) {
            $this->problems[] = 'O servidor "' . $name . '" não está cadastrado neste ambiente.';
            return null;
        }
        if ($matches->count() > 1) {
            $this->problems[] = 'Há mais de um servidor chamado "' . $name . '" neste ambiente.';
            return null;
        }

        $server = $matches->first();
        if (!$server->legacy_config_id) {
            $this->warnings[] = 'O servidor "' . $name . '" não tem código de vínculo; o modelo ficará sem servidor e não '
                . 'preencherá os dados do processo até ser vinculado.';
            return null;
        }

        return $server;
    }

    /**
     * Listas associadas sao citadas pelo ID do grupo dentro de origem_alias;
     * o pacote leva o nome e aqui ele volta a ser o ID deste ambiente.
     * Devolve [chave do campo => id_grupo].
     */
    private function resolveLists(array $fields)
    {
        $groups = null;
        $ids = [];

        foreach ($fields as $definition) {
            $key = $definition['key'] ?? null;
            $alias = (string) ($definition['origem_alias'] ?? '');
            $usesList = Str::startsWith(trim($alias), 'tp_lista_tb_|_') && preg_match('/id_grupo=\d+/i', $alias);
            $name = trim((string) ($definition['lista_grupo'] ?? ''));
            $label = '"' . ($definition['rotulo'] ?? $key) . '"';

            if (!$usesList || !is_string($key)) {
                continue;
            }
            if ($name === '') {
                $this->warnings[] = 'O campo ' . $label . ' usa uma lista indicada apenas pelo número do ambiente de origem; '
                    . 'confira a lista associada depois de importar.';
                continue;
            }

            $groups = $groups ?: ListaGrupo::orderBy('id_grupo')->get();
            $matches = $groups->filter(function ($group) use ($name) {
                return $this->same($group->nome_grupo, $name);
            })->values();

            if ($matches->count() === 1) {
                $ids[$key] = (int) $matches->first()->id_grupo;
            } elseif ($matches->count() === 0) {
                $this->problems[] = 'O campo ' . $label . ' usa a lista "' . $name . '", que não existe neste ambiente. '
                    . 'Cadastre a lista antes de importar.';
            } else {
                $this->problems[] = 'O campo ' . $label . ' usa a lista "' . $name . '", e há mais de uma lista com esse nome neste ambiente.';
            }
        }

        return $ids;
    }

    private function store(array $package, $slug, $client, $sector, $server, array $listIds, $status)
    {
        $model = PeticaoModelo::where('slug', $slug)->first();
        if (!$model) {
            $model = new PeticaoModelo();
            $model->legacy_tipo_id = null;
        }

        $model->fill(array_merge(array_intersect_key($package['model'], array_flip(self::MODEL_ATTRIBUTES)), [
            'slug' => $slug,
            'legacy_cliente_id' => $client ? $client->cliente_id : null,
            'legacy_setor_id' => $sector ? $sector->id_setor : null,
            'legacy_sql_config_id' => $server ? $server->legacy_config_id : null,
            'status' => $status,
        ]));
        $model->save();

        $oldFieldIds = PeticaoModeloCampo::where('modelo_id', $model->id)->pluck('id')->all();
        if ($oldFieldIds) {
            PeticaoModeloCampoOpcao::whereIn('campo_id', $oldFieldIds)->delete();
        }
        PeticaoModeloParagrafo::where('modelo_id', $model->id)->delete();
        PeticaoModeloCampo::where('modelo_id', $model->id)->delete();

        // 1a passada: cria os campos para conhecer os IDs deste ambiente.
        $map = [];
        $created = [];
        foreach ($package['fields'] as $definition) {
            $key = $definition['key'];
            $options = $definition['options'] ?? ($package['fields_by_key'][$key]['options'] ?? []);

            $attributes = array_intersect_key($definition, array_flip(self::FIELD_ATTRIBUTES));
            if (isset($listIds[$key])) {
                $attributes['origem_alias'] = preg_replace('/id_grupo=\d+/i', 'id_grupo=' . $listIds[$key], (string) $attributes['origem_alias']);
            }
            $attributes['modelo_id'] = $model->id;
            $attributes['legacy_input_id'] = null;
            $attributes['token'] = '@import-' . $model->id . '-' . md5($key) . '@';

            $field = PeticaoModeloCampo::create($attributes);
            $field->token = '@modelo' . $model->id . '_campo' . $field->id . '@';
            $field->save();

            $map['{{field:' . $key . '}}'] = '@campo' . $field->id . '@';
            $map['{{field_id:' . $key . '}}'] = (string) $field->id;
            $created[] = [$field, is_array($options) ? $options : []];
        }

        // 2a passada: opcoes (os scripts tambem citam o numero delas).
        foreach ($created as $pair) {
            list($field, $options) = $pair;
            $key = array_search('@campo' . $field->id . '@', $map, true);
            $key = $key ? substr($key, strlen('{{field:'), -2) : null;

            foreach (array_values($options) as $index => $option) {
                if (!is_array($option)) {
                    continue;
                }
                $opcao = PeticaoModeloCampoOpcao::create([
                    'campo_id' => $field->id,
                    'legacy_dado_id' => null,
                    'rotulo' => (string) ($option['rotulo'] ?? ''),
                    'valor_retorno' => $this->resolveReferences($option['valor_retorno'] ?? null, $map),
                    'valores_extras' => $option['valores_extras'] ?? null,
                    'ordem' => (int) ($option['ordem'] ?? $index),
                ]);
                if ($key !== null) {
                    $map['{{option_id:' . $key . ':' . $index . '}}'] = (string) $opcao->id_dados;
                }
            }
        }

        // 3a passada: com todos os IDs conhecidos, troca as referencias.
        foreach ($created as $pair) {
            list($field) = $pair;

            $field->eventos_frontend = $this->resolveEvents($field->eventos_frontend, $map);
            $field->prefixo = $this->resolveReferences($field->prefixo, $map);
            $field->sufixo = $this->resolveReferences($field->sufixo, $map);
            $field->valor_padrao = $this->resolveReferences($field->valor_padrao, $map);
            $field->save();
        }

        $model->cabecalho_html = $this->resolveReferences($model->cabecalho_html, $map);
        $model->rodape_html = $this->resolveReferences($model->rodape_html, $map);
        $model->metadata = $this->resolveReferences($model->metadata, $map);
        $model->save();

        foreach (array_values($package['paragraphs']) as $index => $paragraph) {
            PeticaoModeloParagrafo::create([
                'modelo_id' => $model->id,
                'legacy_fund_id' => null,
                'titulo' => $paragraph['titulo'] ?? null,
                'conteudo_html' => $this->resolveReferences($paragraph['conteudo_html'] ?? '', $map),
                'ordem' => (int) ($paragraph['ordem'] ?? $index),
                'visivel' => array_key_exists('visivel', $paragraph) ? (bool) $paragraph['visivel'] : true,
                'ativo' => array_key_exists('ativo', $paragraph) ? (bool) $paragraph['ativo'] : true,
            ]);
        }
    }

    private function resolveEvents($events, array $map)
    {
        if (!is_array($events)) {
            return $events;
        }

        $events = $this->resolveReferences($events, $map);
        if (isset($events['conditional']['target_ids']) && is_array($events['conditional']['target_ids'])) {
            $events['conditional']['target_ids'] = array_values(array_filter(array_map('intval', $events['conditional']['target_ids'])));
        }
        // A configuracao da dinamica guarda os campos como numero.
        if (isset($events['dinamica']) && is_array($events['dinamica'])) {
            foreach (['alvo', 'pessoa', 'documento'] as $name) {
                if (isset($events['dinamica'][$name]) && is_numeric($events['dinamica'][$name])) {
                    $events['dinamica'][$name] = (int) $events['dinamica'][$name];
                }
            }
            if (isset($events['dinamica']['alvos']) && is_array($events['dinamica']['alvos'])) {
                $events['dinamica']['alvos'] = array_map('intval', $events['dinamica']['alvos']);
            }
            foreach ((array) ($events['dinamica']['preenche'] ?? []) as $index => $fill) {
                if (is_array($fill) && isset($fill['alvo'])) {
                    $events['dinamica']['preenche'][$index]['alvo'] = (int) $fill['alvo'];
                }
            }
        }

        return $events;
    }

    private function resolveReferences($value, array $map)
    {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = $this->resolveReferences($item, $map);
            }

            return $value;
        }

        return is_string($value) && $value !== '' && $map ? strtr($value, $map) : $value;
    }

    private function same($left, $right)
    {
        return $this->normalize($left) === $this->normalize($right);
    }

    private function normalize($value)
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}
