<?php

namespace App\Services;

use App\User;
use App\UserLanguageToolPreference;

class PeticaoSavedReviewService
{
    const MAX_ISSUES = 50;

    protected $client;

    public function __construct(LanguageToolClient $client)
    {
        $this->client = $client;
    }

    public function review($html, $plainText = null, User $user = null)
    {
        $text = $this->normalizeReviewText($plainText !== null ? $plainText : $this->extractPlainText($html));

        if ($text === '') {
            return [
                'mode' => 'languagetool',
                'summary' => 'O documento esta vazio. Nao ha conteudo para revisar.',
                'score' => 100,
                'issues' => [],
                'warnings' => [],
            ];
        }

        $response = $this->client->check($text);
        if (!$response['ok']) {
            return [
                'mode' => 'languagetool',
                'summary' => 'Nao foi possivel concluir a revisao ortografica e gramatical.',
                'score' => 0,
                'issues' => [],
                'warnings' => array_values(array_filter([
                    $response['error'] ?? 'Falha ao consultar o LanguageTool.',
                ])),
            ];
        }

        $preferences = $this->loadUserPreferences($user);
        $issues = $this->normalizeIssues($response['data']['matches'] ?? [], $text, $preferences);
        $warnings = [];

        if (count($issues) >= self::MAX_ISSUES && count($response['data']['matches'] ?? []) > self::MAX_ISSUES) {
            $warnings[] = 'A revisao foi limitada aos primeiros ' . self::MAX_ISSUES . ' achados para manter a interface utilizavel.';
        }

        return [
            'mode' => 'languagetool',
            'summary' => $this->buildSummary($issues),
            'score' => $this->calculateScore($issues),
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    public function storePreference(User $user, array $payload)
    {
        $entryType = trim((string) ($payload['entry_type'] ?? ''));
        $token = $this->normalizePreferenceToken($payload['token'] ?? '');
        $ruleId = trim((string) ($payload['rule_id'] ?? ''));

        if (!in_array($entryType, ['ignored_match', 'dictionary_word'], true)) {
            throw new \InvalidArgumentException('Tipo de preferencia do LanguageTool invalido.');
        }

        if ($token === '') {
            throw new \InvalidArgumentException('Nenhum texto valido foi informado para a preferencia.');
        }

        if ($entryType === 'dictionary_word' && preg_match('/\s/u', $token)) {
            throw new \InvalidArgumentException('A adicao ao dicionario aceita apenas uma palavra por vez.');
        }

        UserLanguageToolPreference::query()->firstOrCreate([
            'user_id' => $user->id,
            'entry_type' => $entryType,
            'token' => $token,
            'rule_id' => $entryType === 'ignored_match' ? $ruleId : null,
        ]);
    }

    protected function normalizeIssues(array $matches, $text, array $preferences = [])
    {
        $issues = [];

        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }

            $offset = max(0, (int) ($match['offset'] ?? 0));
            $length = max(0, (int) ($match['length'] ?? 0));
            if ($length === 0) {
                continue;
            }

            $snippet = $this->buildSnippet($text, $offset, $length);
            $replacements = $this->extractReplacements($match);

            $issues[] = [
                'category' => $this->resolveCategory($match),
                'severity' => $this->resolveSeverity($match),
                'snippet' => $snippet,
                'message' => trim((string) ($match['message'] ?? '')),
                'suggestion' => isset($replacements[0]) ? $replacements[0] : '',
                'replacements' => $replacements,
                'offset' => $offset,
                'length' => $length,
                'rule_id' => trim((string) data_get($match, 'rule.id', '')),
            ];

            $lastIssue = $issues[count($issues) - 1];
            if ($this->shouldIgnoreIssue($lastIssue, $preferences)) {
                array_pop($issues);
                continue;
            }

            if (count($issues) >= self::MAX_ISSUES) {
                break;
            }
        }

        return array_values(array_filter($issues, function ($issue) {
            return $issue['message'] !== '' && $issue['snippet'] !== '';
        }));
    }

    protected function buildSummary(array $issues)
    {
        if (empty($issues)) {
            return 'Nenhum erro ortografico ou gramatical foi encontrado nesta revisao.';
        }

        $high = $this->countIssuesBySeverity($issues, 'alta');
        $medium = $this->countIssuesBySeverity($issues, 'media');
        $low = $this->countIssuesBySeverity($issues, 'baixa');

        $parts = [];
        if ($high > 0) {
            $parts[] = $high . ' erro' . ($high === 1 ? '' : 's') . ' critico' . ($high === 1 ? '' : 's');
        }
        if ($medium > 0) {
            $parts[] = $medium . ' alerta' . ($medium === 1 ? '' : 's') . ' gramatical' . ($medium === 1 ? '' : 'is');
        }
        if ($low > 0) {
            $parts[] = $low . ' ajuste' . ($low === 1 ? '' : 's') . ' de estilo ou pontuacao';
        }

        return 'Revisao concluida: ' . implode(', ', $parts) . '.';
    }

    protected function calculateScore(array $issues)
    {
        $score = 100;

        foreach ($issues as $issue) {
            if (($issue['severity'] ?? '') === 'alta') {
                $score -= 10;
                continue;
            }

            if (($issue['severity'] ?? '') === 'media') {
                $score -= 5;
                continue;
            }

            $score -= 2;
        }

        return max(0, $score);
    }

    protected function countIssuesBySeverity(array $issues, $severity)
    {
        return count(array_filter($issues, function ($issue) use ($severity) {
            return ($issue['severity'] ?? '') === $severity;
        }));
    }

    protected function resolveCategory(array $match)
    {
        $categoryName = trim((string) data_get($match, 'rule.category.name', ''));
        if ($categoryName !== '') {
            return $categoryName;
        }

        $categoryId = strtoupper(trim((string) data_get($match, 'rule.category.id', '')));
        if (strpos($categoryId, 'TYP') !== false || strpos($categoryId, 'SPELL') !== false) {
            return 'Ortografia';
        }

        if (strpos($categoryId, 'GRAM') !== false || strpos($categoryId, 'AGREEMENT') !== false) {
            return 'Gramatica';
        }

        if (strpos($categoryId, 'PUNCT') !== false) {
            return 'Pontuacao';
        }

        if (strpos($categoryId, 'STYLE') !== false) {
            return 'Estilo';
        }

        return 'Revisao';
    }

    protected function resolveSeverity(array $match)
    {
        $issueType = strtolower(trim((string) data_get($match, 'rule.issueType', '')));
        $categoryId = strtoupper(trim((string) data_get($match, 'rule.category.id', '')));

        if (in_array($issueType, ['misspelling', 'typographical'], true)) {
            return 'alta';
        }

        if (
            strpos($categoryId, 'SPELL') !== false
            || strpos($categoryId, 'TYP') !== false
            || strpos($categoryId, 'CASING') !== false
        ) {
            return 'alta';
        }

        if (
            in_array($issueType, ['grammar', 'duplication'], true)
            || strpos($categoryId, 'GRAM') !== false
            || strpos($categoryId, 'AGREEMENT') !== false
        ) {
            return 'media';
        }

        return 'baixa';
    }

    protected function extractReplacements(array $match)
    {
        $replacements = [];

        foreach ((array) ($match['replacements'] ?? []) as $replacement) {
            $value = trim((string) ($replacement['value'] ?? ''));
            if ($value === '' || in_array($value, $replacements, true)) {
                continue;
            }

            $replacements[] = $value;

            if (count($replacements) >= 5) {
                break;
            }
        }

        return $replacements;
    }

    protected function buildSnippet($text, $offset, $length)
    {
        $snippet = mb_substr($text, $offset, $length, 'UTF-8');

        return trim($snippet);
    }

    protected function extractPlainText($html)
    {
        $html = (string) $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace(
            '/<\/(?:address|article|aside|blockquote|dd|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|li|main|nav|ol|p|pre|section|table|tbody|td|tfoot|th|thead|tr|ul)>/i',
            "$0\n",
            $html
        );
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

        return $this->normalizeReviewText($html);
    }

    protected function normalizeReviewText($text)
    {
        $text = str_replace("\xc2\xa0", ' ', (string) $text);
        $text = preg_replace("/\r\n|\r|\n/u", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    protected function loadUserPreferences(User $user = null)
    {
        if (!$user) {
            return [
                'dictionary_words' => [],
                'ignored_matches' => [],
            ];
        }

        $entries = UserLanguageToolPreference::query()
            ->where('user_id', $user->id)
            ->get();

        $dictionaryWords = [];
        $ignoredMatches = [];

        foreach ($entries as $entry) {
            $token = $this->normalizePreferenceToken($entry->token);
            if ($token === '') {
                continue;
            }

            if ($entry->entry_type === 'dictionary_word') {
                $dictionaryWords[$token] = true;
                continue;
            }

            if ($entry->entry_type === 'ignored_match') {
                $key = $token . '|' . mb_strtolower(trim((string) $entry->rule_id), 'UTF-8');
                $ignoredMatches[$key] = true;
            }
        }

        return [
            'dictionary_words' => $dictionaryWords,
            'ignored_matches' => $ignoredMatches,
        ];
    }

    protected function shouldIgnoreIssue(array $issue, array $preferences)
    {
        $snippet = $this->normalizePreferenceToken($issue['snippet'] ?? '');
        $ruleId = mb_strtolower(trim((string) ($issue['rule_id'] ?? '')), 'UTF-8');
        if ($snippet === '') {
            return false;
        }

        if (!empty($preferences['dictionary_words'][$snippet])) {
            return true;
        }

        if (!empty($preferences['ignored_matches'][$snippet . '|' . $ruleId])) {
            return true;
        }

        if (!empty($preferences['ignored_matches'][$snippet . '|'])) {
            return true;
        }

        return false;
    }

    protected function normalizePreferenceToken($value)
    {
        $value = $this->normalizeReviewText($value);

        return mb_strtolower($value, 'UTF-8');
    }
}
