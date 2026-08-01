<?php

namespace App\Services;

use App\InputCampo;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\PeticaoModeloParagrafo;
use App\Tipo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyModeloSyncService
{
    public function syncTipo(Tipo $tipo)
    {
        $tipo->loadMissing(['paragrafos', 'campos.dados']);

        return DB::transaction(function () use ($tipo) {
            $modelo = PeticaoModelo::updateOrCreate(
                ['legacy_tipo_id' => $tipo->tipo_id],
                [
                    'legacy_cliente_id' => $tipo->id_cliente ?: null,
                    'legacy_setor_id' => $tipo->id_setor ?: null,
                    'legacy_sql_config_id' => $tipo->id_db ?: null,
                    'nome' => $tipo->tipo_nome,
                    'slug' => $this->buildSlug($tipo),
                    'status' => $tipo->tipo_stt === 'Y' ? 'ativo' : 'inativo',
                    'arquivo_padrao' => $tipo->tipo_arq,
                    'cabecalho_html' => $tipo->cod_cabec,
                    'rodape_html' => $tipo->cod_rodap,
                    'metadata' => [
                        'nome_pre' => $tipo->nome_pre,
                        'nome_pos' => $tipo->nome_pos,
                    ],
                ]
            );

            $this->syncParagrafos($modelo, $tipo);
            $this->syncCampos($modelo, $tipo);

            return $modelo->fresh(['paragrafos', 'campos.opcoes']);
        });
    }

    public function syncAll()
    {
        $synced = 0;

        Tipo::with(['paragrafos', 'campos.dados'])
            ->orderBy('tipo_id')
            ->chunk(50, function ($tipos) use (&$synced) {
                foreach ($tipos as $tipo) {
                    $this->syncTipo($tipo);
                    $synced++;
                }
            });

        return $synced;
    }

    protected function syncParagrafos(PeticaoModelo $modelo, Tipo $tipo)
    {
        $legacyIds = [];

        foreach ($tipo->paragrafos as $paragrafo) {
            $legacyIds[] = $paragrafo->fund_id;

            PeticaoModeloParagrafo::updateOrCreate(
                ['legacy_fund_id' => $paragrafo->fund_id],
                [
                    'modelo_id' => $modelo->id,
                    'titulo' => $paragrafo->fund_titulo,
                    'conteudo_html' => $paragrafo->fund_text,
                    'ordem' => (int) $paragrafo->fund_order,
                    'visivel' => $paragrafo->fund_visi === 'Y',
                    'ativo' => $paragrafo->fund_stt === 'Y',
                ]
            );
        }

        $query = PeticaoModeloParagrafo::where('modelo_id', $modelo->id);
        if (!empty($legacyIds)) {
            $query->whereNotIn('legacy_fund_id', $legacyIds);
        }
        $query->delete();
    }

    protected function syncCampos(PeticaoModelo $modelo, Tipo $tipo)
    {
        $legacyIds = [];

        foreach ($tipo->campos as $campo) {
            $legacyIds[] = $campo->id_input;

            $mirrorCampo = PeticaoModeloCampo::updateOrCreate(
                ['legacy_input_id' => $campo->id_input],
                [
                    'modelo_id' => $modelo->id,
                    'rotulo' => $campo->input_title,
                    'token' => $campo->placeholder,
                    'tipo' => $campo->input_tipo,
                    'comportamento' => $this->normalizeLegacyBehavior($campo->input_alt),
                    'origem_coluna' => $campo->input_val,
                    'origem_alias' => $campo->input_db,
                    'prefixo' => $campo->input_pre,
                    'sufixo' => $campo->input_pos,
                    'valor_padrao' => $campo->texto_padrao,
                    'classe_css' => $campo->add_class,
                    'largura' => $campo->input_width,
                    'colunas_layout' => $campo->input_cols,
                    'linhas_layout' => $campo->input_rols,
                    'ordem' => $campo->input_order ?: 0,
                    'obrigatorio' => (string) $campo->input_req === '1',
                    'visivel' => (string) $campo->hide !== 'none',
                    'gera_nome_arquivo' => $campo->nomepet === 'Y',
                    'eventos_frontend' => $this->extractFrontendEvents($campo),
                ]
            );

            $this->syncCampoOpcoes($mirrorCampo, $campo);
        }

        $query = PeticaoModeloCampo::where('modelo_id', $modelo->id);
        if (!empty($legacyIds)) {
            $query->whereNotIn('legacy_input_id', $legacyIds);
        }

        $campoIdsToDelete = $query->pluck('id');
        if ($campoIdsToDelete->isNotEmpty()) {
            PeticaoModeloCampoOpcao::whereIn('campo_id', $campoIdsToDelete->all())->delete();
            PeticaoModeloCampo::whereIn('id', $campoIdsToDelete->all())->delete();
        }
    }

    protected function syncCampoOpcoes(PeticaoModeloCampo $mirrorCampo, InputCampo $campo)
    {
        $legacyIds = [];

        foreach ($campo->dados as $opcao) {
            $legacyIds[] = $opcao->id_dados;

            PeticaoModeloCampoOpcao::updateOrCreate(
                ['legacy_dado_id' => $opcao->id_dados],
                [
                    'campo_id' => $mirrorCampo->id,
                    'rotulo' => $opcao->nome_dados,
                    'valor_retorno' => $opcao->return_1,
                    'valores_extras' => [
                        'return_2' => $opcao->return_2,
                        'return_3' => $opcao->return_3,
                        'return_4' => $opcao->return_4,
                        'return_5' => $opcao->return_5,
                    ],
                    'ordem' => $opcao->dados_order ?: 0,
                ]
            );
        }

        $query = PeticaoModeloCampoOpcao::where('campo_id', $mirrorCampo->id);
        if (!empty($legacyIds)) {
            $query->whereNotIn('legacy_dado_id', $legacyIds);
        }
        $query->delete();
    }

    protected function buildSlug(Tipo $tipo)
    {
        $slug = Str::slug($tipo->tipo_nome ?: 'modelo');

        if ($slug === '') {
            $slug = 'modelo-' . $tipo->tipo_id;
        }

        return $slug . '-' . $tipo->tipo_id;
    }

    protected function extractFrontendEvents(InputCampo $campo)
    {
        return array_filter([
            'focus' => $campo->input_focu ?: null,
            'load' => $campo->input_load ?: null,
            'blur' => $campo->input_blur ?: null,
        ]);
    }

    protected function normalizeLegacyBehavior($value)
    {
        $value = strtolower(trim((string) $value));

        $map = [
            'date' => 'date',
            'decimal' => 'decimal',
            'cpf' => 'cpf',
            'cnpj' => 'cnpj',
            'fone' => 'fone',
            'cep' => 'cep',
            'integer' => 'integer',
            'numero' => 'integer',
            'número' => 'integer',
        ];

        return $map[$value] ?? '';
    }
}
