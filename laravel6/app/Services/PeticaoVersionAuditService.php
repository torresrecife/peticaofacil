<?php

namespace App\Services;

use App\PeticaoNormalizada;
use App\PeticaoVersao;

class PeticaoVersionAuditService
{
    public function compare(PeticaoNormalizada $peticao, PeticaoVersao $baseVersion, PeticaoVersao $targetVersion = null)
    {
        $targetVersion = $targetVersion ?: $this->buildCurrentStateVersion($peticao);

        $baseLines = $this->normalizeLines($baseVersion->conteudo_html_snapshot);
        $targetLines = $this->normalizeLines($targetVersion->conteudo_html_snapshot);
        $max = max(count($baseLines), count($targetLines));

        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $left = $baseLines[$i] ?? '';
            $right = $targetLines[$i] ?? '';

            $status = 'same';
            if ($left === '' && $right !== '') {
                $status = 'added';
            } elseif ($left !== '' && $right === '') {
                $status = 'removed';
            } elseif ($left !== $right) {
                $status = 'changed';
            }

            $rows[] = [
                'line' => $i + 1,
                'status' => $status,
                'left' => $left,
                'right' => $right,
            ];
        }

        return [
            'base' => $baseVersion,
            'target' => $targetVersion,
            'rows' => $rows,
            'summary' => [
                'changed' => count(array_filter($rows, function ($row) { return $row['status'] === 'changed'; })),
                'added' => count(array_filter($rows, function ($row) { return $row['status'] === 'added'; })),
                'removed' => count(array_filter($rows, function ($row) { return $row['status'] === 'removed'; })),
            ],
        ];
    }

    protected function buildCurrentStateVersion(PeticaoNormalizada $peticao)
    {
        $version = new PeticaoVersao();
        $version->versao_numero = 0;
        $version->cliente_referencia_snapshot = $peticao->cliente_referencia;
        $version->conteudo_html_snapshot = $peticao->conteudo_html;
        $version->origem_snapshot = 'current';
        $version->criado_em = $peticao->salvo_em ?: $peticao->updated_at;

        return $version;
    }

    protected function normalizeLines($html)
    {
        $normalized = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
        $normalized = preg_replace("/\r\n|\r/u", "\n", $normalized);
        $normalized = preg_replace("/\n{2,}/u", "\n", $normalized);

        return preg_split("/\n/u", trim((string) $normalized)) ?: [];
    }
}
