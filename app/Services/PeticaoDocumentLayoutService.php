<?php

namespace App\Services;

use App\PeticaoModelo;
use App\PeticaoNormalizada;
use App\PeticaoVersao;

class PeticaoDocumentLayoutService
{
    public function fromSavedPeticao(PeticaoNormalizada $peticao)
    {
        $peticao->loadMissing('modelo');

        return $this->buildLayout(
            $peticao->cliente_referencia ?: ('peticao_' . $peticao->id),
            $peticao->conteudo_html,
            $peticao->modelo,
            [
                'modelo' => optional($peticao->modelo)->nome,
                'codigo' => $peticao->codigo_externo,
                'peticao_id' => $peticao->id,
                'modelo_id' => $peticao->modelo_id,
            ]
        );
    }

    public function fromVersion(PeticaoNormalizada $peticao, PeticaoVersao $versao)
    {
        $peticao->loadMissing('modelo');

        return $this->buildLayout(
            $versao->cliente_referencia_snapshot ?: ('peticao_versao_' . $versao->versao_numero),
            $versao->conteudo_html_snapshot,
            $peticao->modelo,
            [
                'modelo' => optional($peticao->modelo)->nome,
                'codigo' => $versao->codigo_externo_snapshot,
                'peticao_id' => $peticao->id,
                'versao_id' => $versao->id,
                'versao_numero' => $versao->versao_numero,
                'modelo_id' => $peticao->modelo_id,
            ]
        );
    }

    public function fromEditorDraft(PeticaoModelo $modelo, $nomeArquivo, $conteudoHtml, array $meta = [])
    {
        return $this->buildLayout($nomeArquivo, $conteudoHtml, $modelo, $meta);
    }

    protected function buildLayout($title, $bodyHtml, $modelo = null, array $meta = [])
    {
        return [
            'title' => (string) $title,
            'body_html' => (string) $bodyHtml,
            'header_html' => $modelo ? (string) ($modelo->cabecalho_html ?: '') : '',
            'footer_html' => $modelo ? (string) ($modelo->rodape_html ?: '') : '',
            'meta' => $meta,
        ];
    }
}
