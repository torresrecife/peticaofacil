<?php

namespace App\Services;

use App\InputCampo;
use App\InputDado;
use App\Paragrafo;
use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\PeticaoModeloCampoOpcao;
use App\PeticaoModeloParagrafo;
use App\Tipo;
use Illuminate\Support\Facades\DB;

class NormalizedModeloLegacySyncService
{
    public function sync(PeticaoModelo $modelo)
    {
        return DB::transaction(function () use ($modelo) {
            $modelo->loadMissing(['paragrafos', 'campos.opcoes']);

            $legacyTipo = $modelo->legacy_tipo_id ? Tipo::find($modelo->legacy_tipo_id) : new Tipo();
            if (!$legacyTipo) {
                $legacyTipo = new Tipo();
            }

            if (!$legacyTipo->exists) {
                $legacyTipo->tipo_data = now();
            }

            $legacyTipo->id_db = $modelo->legacy_sql_config_id ?: null;
            $legacyTipo->nome_pre = $modelo->nome_pre;
            $legacyTipo->nome_pos = $modelo->nome_pos;
            $legacyTipo->tipo_nome = $modelo->nome;
            $legacyTipo->id_cliente = $modelo->legacy_cliente_id ?: null;
            $legacyTipo->tipo_stt = $modelo->status === 'ativo' ? 'Y' : 'N';
            $legacyTipo->id_setor = $modelo->legacy_setor_id;
            $legacyTipo->cod_cabec = $modelo->cabecalho_html;
            $legacyTipo->cod_rodap = $modelo->rodape_html;
            $legacyTipo->tipo_arq = $modelo->arquivo_padrao;
            $legacyTipo->save();

            if ((int) $modelo->legacy_tipo_id !== (int) $legacyTipo->tipo_id) {
                $modelo->legacy_tipo_id = $legacyTipo->tipo_id;
                $modelo->save();
            }

            $this->syncParagrafos($modelo, $legacyTipo);
            $this->syncCampos($modelo, $legacyTipo);

            return $legacyTipo->fresh(['paragrafos', 'campos.dados']);
        });
    }

    protected function syncParagrafos(PeticaoModelo $modelo, Tipo $legacyTipo)
    {
        $legacyIds = [];

        foreach ($modelo->paragrafos as $paragrafo) {
            $legacy = $paragrafo->legacy_fund_id ? Paragrafo::find($paragrafo->legacy_fund_id) : new Paragrafo();
            if (!$legacy) {
                $legacy = new Paragrafo();
            }

            $legacy->tipo_id = $legacyTipo->tipo_id;
            $legacy->fund_titulo = $paragrafo->fund_titulo;
            $legacy->fund_text = $paragrafo->fund_text;
            $legacy->fund_order = $paragrafo->fund_order;
            $legacy->fund_visi = $paragrafo->visivel ? 'Y' : 'N';
            $legacy->fund_stt = $paragrafo->ativo ? 'Y' : 'N';
            $legacy->fund_data = $legacy->fund_data ?: now()->format('Y-m-d');
            $legacy->save();

            if ((int) $paragrafo->legacy_fund_id !== (int) $legacy->fund_id) {
                $paragrafo->legacy_fund_id = $legacy->fund_id;
                $paragrafo->save();
            }

            $legacyIds[] = $legacy->fund_id;
        }

        $query = Paragrafo::where('tipo_id', $legacyTipo->tipo_id);
        if (!empty($legacyIds)) {
            $query->whereNotIn('fund_id', $legacyIds);
        }
        $query->delete();
    }

    protected function syncCampos(PeticaoModelo $modelo, Tipo $legacyTipo)
    {
        $legacyIds = [];

        foreach ($modelo->campos as $campo) {
            $legacyCampo = $campo->legacy_input_id ? InputCampo::find($campo->legacy_input_id) : new InputCampo();
            if (!$legacyCampo) {
                $legacyCampo = new InputCampo();
            }

            $legacyCampo->tipo_id = $legacyTipo->tipo_id;
            $legacyCampo->input_pre = $campo->input_pre;
            $legacyCampo->input_pos = $campo->input_pos;
            $legacyCampo->input_title = $campo->input_title;
            $legacyCampo->input_tipo = $campo->input_tipo;
            $legacyCampo->input_db = $campo->input_db;
            $legacyCampo->input_val = $campo->input_val;
            $legacyCampo->input_alt = $this->denormalizeBehavior($campo->input_behavior);
            $legacyCampo->input_cols = $campo->input_cols ?: 1;
            $legacyCampo->input_rols = $campo->input_rols ?: 0;
            $legacyCampo->input_focu = $campo->input_focu;
            $legacyCampo->input_load = $campo->input_load;
            $legacyCampo->input_blur = $campo->input_blur;
            $legacyCampo->input_width = $campo->input_width ?: $this->resolveWidth((int) $campo->input_cols);
            $legacyCampo->input_req = $campo->input_req ?: 0;
            $legacyCampo->input_order = $campo->input_order ?: 0;
            $legacyCampo->listsel = 'N';
            $legacyCampo->nomepet = $campo->nomepet;
            $legacyCampo->hide = $campo->hide;
            $legacyCampo->texto_padrao = $campo->texto_padrao;
            $legacyCampo->add_class = $campo->add_class;
            $legacyCampo->save();

            if ((int) $campo->legacy_input_id !== (int) $legacyCampo->id_input) {
                $campo->legacy_input_id = $legacyCampo->id_input;
                $campo->save();
            }

            $this->syncCampoOpcoes($campo, $legacyCampo);
            $legacyIds[] = $legacyCampo->id_input;
        }

        $query = InputCampo::where('tipo_id', $legacyTipo->tipo_id)->where('listsel', 'N');
        if (!empty($legacyIds)) {
            $query->whereNotIn('id_input', $legacyIds);
        }

        $legacyFieldIdsToDelete = $query->pluck('id_input');
        if ($legacyFieldIdsToDelete->isNotEmpty()) {
            InputDado::whereIn('id_input', $legacyFieldIdsToDelete->all())->where('listsel', 'N')->delete();
            InputCampo::whereIn('id_input', $legacyFieldIdsToDelete->all())->delete();
        }
    }

    protected function syncCampoOpcoes(PeticaoModeloCampo $campo, InputCampo $legacyCampo)
    {
        InputDado::where('id_input', $legacyCampo->id_input)->where('listsel', 'N')->delete();

        if ($campo->input_tipo !== 'SELECT') {
            return;
        }

        foreach ($campo->opcoes as $index => $opcao) {
            $legacyOpcao = new InputDado();
            $legacyOpcao->id_input = $legacyCampo->id_input;
            $legacyOpcao->nome_dados = $opcao->nome_dados;
            $legacyOpcao->return_1 = $opcao->return_1 ?: $opcao->nome_dados;
            $legacyOpcao->data_cad = now();
            $legacyOpcao->dados_order = $opcao->dados_order ?: ($index + 1);
            $legacyOpcao->id_setor = 1;
            $legacyOpcao->listsel = 'N';
            $legacyOpcao->save();

            if ((int) $opcao->legacy_dado_id !== (int) $legacyOpcao->id_dados) {
                $opcao->legacy_dado_id = $legacyOpcao->id_dados;
                $opcao->save();
            }
        }
    }

    protected function resolveWidth($cols)
    {
        if ($cols === 2) {
            return 560;
        }
        if ($cols === 3) {
            return 860;
        }

        return 265;
    }

    protected function denormalizeBehavior($behavior)
    {
        $behavior = strtolower(trim((string) $behavior));

        $allowed = ['date', 'decimal', 'cpf', 'cnpj', 'fone', 'cep', 'integer'];

        return in_array($behavior, $allowed, true) ? $behavior : '';
    }
}
