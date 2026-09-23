<?php

namespace App\Console\Commands;

use App\Cliente;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\PeticaoModeloParagrafo;
use App\Setor;
use App\SqlServerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportStoneExecutionModel extends Command
{
    protected $signature = 'peticao:modelo-stone-import
        {--dry-run : Apenas valida o pacote e as dependências}
        {--activate : Ativa o modelo após a importação}';

    protected $description = 'Importa ou atualiza o modelo Stone de execução de título extrajudicial';

    public function handle()
    {
        $path = database_path('data/stone-execucao-titulo-extrajudicial.json');
        if (!is_file($path)) {
            $this->error('Pacote não encontrado: ' . $path);
            return 1;
        }

        $package = json_decode(file_get_contents($path), true);
        if (!is_array($package) || empty($package['model']) || empty($package['fields']) || empty($package['paragraphs'])) {
            $this->error('Pacote inválido.');
            return 1;
        }

        $client = Cliente::whereRaw('UPPER(cliente_name) = ?', [strtoupper($package['dependencies']['client'])])->first();
        $sector = Setor::where('cod_setor', $package['dependencies']['sector_code'])->first();
        $server = SqlServerProfile::where('nome', $package['dependencies']['server'])->first();
        $missing = array_keys(array_filter(['cliente' => !$client, 'setor' => !$sector, 'servidor NEO' => !$server]));
        if ($missing) {
            $this->error('Dependências ausentes: ' . implode(', ', $missing));
            return 1;
        }

        $keys = array_column($package['fields'], 'key');
        if (count($keys) !== count(array_unique($keys))) {
            $this->error('O pacote contém chaves de campos duplicadas.');
            return 1;
        }
        preg_match_all('/\{\{field(?:_id)?:([^}]+)\}\}/', json_encode($package), $references);
        $unknownReferences = array_diff(array_unique($references[1] ?? []), $keys);
        if ($unknownReferences) {
            $this->error('Referências de campos desconhecidas: ' . implode(', ', $unknownReferences));
            return 1;
        }

        $this->line('Cliente: ' . $client->cliente_name);
        $this->line('Setor: ' . $sector->nome_setor);
        $this->line('Servidor: ' . $server->nome);
        $this->line('Campos: ' . count($package['fields']) . '; parágrafos: ' . count($package['paragraphs']));
        if ($this->option('dry-run')) {
            $this->info('Pacote validado. Nenhuma alteração foi gravada.');
            return 0;
        }

        DB::transaction(function () use ($package, $client, $sector, $server) {
            $model = PeticaoModelo::where('slug', $package['model']['slug'])->first() ?: new PeticaoModelo();
            $model->fill(array_merge($package['model'], [
                'legacy_tipo_id' => null,
                'legacy_cliente_id' => $client->cliente_id,
                'legacy_setor_id' => $sector->id_setor,
                'legacy_sql_config_id' => $server->legacy_config_id,
                'status' => $this->option('activate') ? 'ativo' : 'inativo',
            ]));
            $model->save();

            $model->paragrafos()->delete();
            $model->campos()->delete();
            $tokens = [];
            foreach ($package['fields'] as $definition) {
                $key = $definition['key'];
                unset($definition['key'], $definition['options']);
                $definition['modelo_id'] = $model->id;
                $definition['legacy_input_id'] = null;
                $definition['token'] = '@stone-import-' . $model->id . '-' . preg_replace('/[^a-z0-9]+/', '-', $key) . '@';
                $field = PeticaoModeloCampo::create($definition);
                $field->token = '@modelo' . $model->id . '_campo' . $field->id . '@';
                $field->eventos_frontend = $this->replaceReferences($field->eventos_frontend ?: [], $tokens + [$key => $field->placeholder]);
                $field->save();
                $tokens[$key] = $field->placeholder;

                foreach (($package['fields_by_key'][$key]['options'] ?? []) as $option) {
                    $option['campo_id'] = $field->id;
                    $option['legacy_dado_id'] = null;
                    PeticaoModeloCampoOpcao::create($option);
                }
            }

            // Events can reference fields created later, so resolve them in a second pass.
            foreach ($model->campos()->get() as $field) {
                $field->eventos_frontend = $this->replaceReferences($field->eventos_frontend ?: [], $tokens);
                $field->save();
            }
            foreach ($package['paragraphs'] as $paragraph) {
                $paragraph['modelo_id'] = $model->id;
                $paragraph['legacy_fund_id'] = null;
                $paragraph['conteudo_html'] = $this->replaceReferences($paragraph['conteudo_html'], $tokens);
                PeticaoModeloParagrafo::create($paragraph);
            }
        });

        $this->info('Modelo Stone importado com sucesso no status ' . ($this->option('activate') ? 'ativo' : 'inativo') . '.');
        return 0;
    }

    private function replaceReferences($value, array $tokens)
    {
        $json = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        foreach ($tokens as $key => $token) {
            $json = str_replace('{{field:' . $key . '}}', $token, $json);
            if (preg_match('/^@campo(\d+)@$/', $token, $match)) {
                $json = str_replace('{{field_id:' . $key . '}}', $match[1], $json);
            }
        }
        return is_array($value) ? (json_decode($json, true) ?: []) : $json;
    }
}
