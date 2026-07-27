<?php

namespace App\Services;

use App\PeticaoNormalizada;
use App\PeticaoVersao;

class PeticaoVersionAuditService
{
    public function compare(PeticaoNormalizada $peticao, PeticaoVersao $baseVersion, PeticaoVersao $targetVersion = null)
    {
        $targetVersion = $targetVersion ?: $this->buildCurrentStateVersion($peticao);

        $baseBlocks = $this->extractBlocks($baseVersion->conteudo_html_snapshot);
        $targetBlocks = $this->extractBlocks($targetVersion->conteudo_html_snapshot);
        $max = max(count($baseBlocks), count($targetBlocks));

        $rows = [];
        for ($i = 0; $i < $max; $i++) {
            $left = $baseBlocks[$i] ?? '';
            $right = $targetBlocks[$i] ?? '';

            $status = 'same';
            if ($left === '' && $right !== '') {
                $status = 'added';
            } elseif ($left !== '' && $right === '') {
                $status = 'removed';
            } elseif ($left !== $right) {
                $status = 'changed';
            }

            $highlighted = $this->highlightLineDiff($left, $right, $status);

            $rows[] = [
                'line' => $i + 1,
                'anchor' => 'block-' . ($i + 1),
                'status' => $status,
                'left' => $left,
                'right' => $right,
                'left_html' => $highlighted['left'],
                'right_html' => $highlighted['right'],
            ];
        }

        $changes = array_values(array_filter($rows, function ($row) {
            return $row['status'] !== 'same';
        }));

        return [
            'base' => $baseVersion,
            'target' => $targetVersion,
            'rows' => $rows,
            'changes' => $changes,
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

    protected function extractBlocks($html)
    {
        preg_match_all('/<(p|div|li|h[1-6]|blockquote|td)[^>]*>.*?<\/\1>/isu', (string) $html, $matches);

        $blocks = [];
        foreach (($matches[0] ?? []) as $blockHtml) {
            $text = html_entity_decode(strip_tags((string) $blockHtml), ENT_QUOTES, 'UTF-8');
            $text = preg_replace("/\s+/u", ' ', trim($text));
            if ($text !== '') {
                $blocks[] = $text;
            }
        }

        if (!empty($blocks)) {
            return $blocks;
        }

        $normalized = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
        $normalized = preg_replace("/\r\n|\r/u", "\n", $normalized);
        $normalized = preg_replace("/\n{2,}/u", "\n", $normalized);

        return array_values(array_filter(preg_split("/\n/u", trim((string) $normalized)) ?: [], function ($line) {
            return trim($line) !== '';
        }));
    }

    protected function highlightLineDiff($left, $right, $status)
    {
        if ($status === 'same') {
            return [
                'left' => e($left),
                'right' => e($right),
            ];
        }

        if ($status === 'added') {
            return [
                'left' => '',
                'right' => '<mark class="diff-added">' . e($right) . '</mark>',
            ];
        }

        if ($status === 'removed') {
            return [
                'left' => '<mark class="diff-removed">' . e($left) . '</mark>',
                'right' => '',
            ];
        }

        $prefixLength = $this->commonPrefixLength($left, $right);
        $suffixLength = $this->commonSuffixLength($left, $right, $prefixLength);

        $leftMiddle = mb_substr($left, $prefixLength, mb_strlen($left) - $prefixLength - $suffixLength);
        $rightMiddle = mb_substr($right, $prefixLength, mb_strlen($right) - $prefixLength - $suffixLength);
        $leftPrefix = mb_substr($left, 0, $prefixLength);
        $rightPrefix = mb_substr($right, 0, $prefixLength);
        $leftSuffix = $suffixLength > 0 ? mb_substr($left, -$suffixLength) : '';
        $rightSuffix = $suffixLength > 0 ? mb_substr($right, -$suffixLength) : '';

        return [
            'left' => e($leftPrefix) . '<mark class="diff-changed">' . e($leftMiddle) . '</mark>' . e($leftSuffix),
            'right' => e($rightPrefix) . '<mark class="diff-changed">' . e($rightMiddle) . '</mark>' . e($rightSuffix),
        ];
    }

    protected function commonPrefixLength($left, $right)
    {
        $max = min(mb_strlen($left), mb_strlen($right));

        for ($i = 0; $i < $max; $i++) {
            if (mb_substr($left, $i, 1) !== mb_substr($right, $i, 1)) {
                return $i;
            }
        }

        return $max;
    }

    protected function commonSuffixLength($left, $right, $prefixLength)
    {
        $leftLength = mb_strlen($left);
        $rightLength = mb_strlen($right);
        $max = min($leftLength, $rightLength) - $prefixLength;

        for ($i = 0; $i < $max; $i++) {
            if (
                mb_substr($left, $leftLength - $i - 1, 1) !==
                mb_substr($right, $rightLength - $i - 1, 1)
            ) {
                return $i;
            }
        }

        return $max;
    }
}
