<?php

namespace App\Services;

use App\PeticaoModeloCampo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Corrige, a partir do sistema antigo, o que a migracao trouxe errado nos
 * campos: o obrigatorio (veio invertido) e as mascaras que ficaram vazias.
 *
 * Trabalha com um mapa id_input do legado => ['req' => 1|2, 'alt' => '...'],
 * lido do banco antigo (comandos peticao:backfill-*) ou do arquivo
 * database/data/legacy-campos.json (migration de deploy). Casa sempre por
 * peticao_modelo_campos.legacy_input_id, nunca pelo id do banco novo, entao
 * vale em qualquer copia do banco.
 */
class LegacyCampoRestoreService
{
    const SNAPSHOT_PATH = 'database/data/legacy-campos.json';

    public function loadSnapshot($path = null)
    {
        $path = $path ?: base_path(self::SNAPSHOT_PATH);
        $data = json_decode((string) @file_get_contents($path), true);
        if (!is_array($data) || !isset($data['fields']) || !is_array($data['fields'])) {
            throw new RuntimeException('Arquivo de campos do legado invalido ou ausente: ' . $path);
        }

        return $data['fields'];
    }

    /**
     * Campos que o sistema novo considera editados na tela depois da migracao.
     */
    public static function wasEditedAfterMigration($campo)
    {
        return $campo->updated_at && $campo->created_at && $campo->updated_at > $campo->created_at;
    }

    /**
     * @return array ['ja_ok', 'a_corrigir', 'editados_mantidos', 'sem_campo_novo', 'updates' => [campo_id => 0|1]]
     */
    public function planRequired(array $legacyFields, $overwrite = false)
    {
        $plan = ['ja_ok' => 0, 'a_corrigir' => 0, 'editados_mantidos' => 0, 'sem_campo_novo' => 0, 'updates' => [], 'editados' => []];
        $seen = [];

        foreach (array_chunk(array_keys($legacyFields), 500) as $chunk) {
            $campos = DB::table('peticao_modelo_campos')
                ->whereIn('legacy_input_id', $chunk)
                ->get(['id', 'legacy_input_id', 'rotulo', 'obrigatorio', 'created_at', 'updated_at']);

            foreach ($campos as $campo) {
                $seen[(string) $campo->legacy_input_id] = true;
                $esperado = PeticaoModeloCampo::mapLegacyRequired($legacyFields[$campo->legacy_input_id]['req'] ?? null) ? 1 : 0;
                if ((int) $campo->obrigatorio === $esperado) {
                    $plan['ja_ok']++;
                    continue;
                }
                if (!$overwrite && static::wasEditedAfterMigration($campo)) {
                    $plan['editados_mantidos']++;
                    $plan['editados'][] = $campo;
                    continue;
                }
                $plan['a_corrigir']++;
                $plan['updates'][$campo->id] = $esperado;
            }
        }

        $plan['sem_campo_novo'] = count($legacyFields) - count($seen);

        return $plan;
    }

    /**
     * So preenche mascara vazia (ou, com $overwrite, a que diverge do legado).
     *
     * @return array ['ja_ok', 'preenchidos', 'divergentes', 'sem_mascara_legado', 'sem_campo_novo', 'updates' => [campo_id => mascara]]
     */
    public function planBehavior(array $legacyFields, $overwrite = false)
    {
        $plan = ['ja_ok' => 0, 'preenchidos' => 0, 'divergentes' => 0, 'sem_mascara_legado' => 0, 'sem_campo_novo' => 0, 'updates' => [], 'divergencias' => []];
        $withBehavior = [];

        foreach ($legacyFields as $legacyId => $field) {
            $behavior = PeticaoModeloCampo::mapLegacyBehavior($field['alt'] ?? '');
            if ($behavior === '') {
                $plan['sem_mascara_legado']++;
                continue;
            }
            $withBehavior[(string) $legacyId] = $behavior;
        }

        $seen = [];
        foreach (array_chunk(array_keys($withBehavior), 500) as $chunk) {
            $campos = DB::table('peticao_modelo_campos')
                ->whereIn('legacy_input_id', $chunk)
                ->get(['id', 'legacy_input_id', 'comportamento']);

            foreach ($campos as $campo) {
                $seen[(string) $campo->legacy_input_id] = true;
                $esperado = $withBehavior[(string) $campo->legacy_input_id];
                $atual = strtolower(trim((string) $campo->comportamento));
                if ($atual === $esperado) {
                    $plan['ja_ok']++;
                    continue;
                }
                if ($atual !== '') {
                    $plan['divergentes']++;
                    if (!$overwrite) {
                        $plan['divergencias'][] = [$campo, $esperado];
                        continue;
                    }
                } else {
                    $plan['preenchidos']++;
                }
                $plan['updates'][$campo->id] = $esperado;
            }
        }

        $plan['sem_campo_novo'] = count($withBehavior) - count($seen);

        return $plan;
    }

    /**
     * Grava o plano numa transacao, sem tocar em updated_at (o campo continua
     * contando como "nao editado na tela"). Devolve as linhas atualizadas.
     */
    public function apply(array $updates, $column)
    {
        $afetados = 0;
        $byValue = [];
        foreach ($updates as $campoId => $value) {
            $byValue[(string) $value][] = $campoId;
        }

        DB::transaction(function () use ($byValue, $column, &$afetados) {
            foreach ($byValue as $value => $ids) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    $afetados += DB::table('peticao_modelo_campos')
                        ->whereIn('id', $chunk)
                        ->update([$column => $column === 'obrigatorio' ? (int) $value : (string) $value]);
                }
            }
        });

        return $afetados;
    }

    /**
     * UPDATEs portaveis, gerados direto do mapa do legado (nao do estado deste
     * banco): casam por legacy_input_id e levam as condicoes do plano, para
     * valer num banco que nao e este (ex.: producao mais nova).
     */
    public function toSql(array $legacyFields, $column, $overwrite = false)
    {
        $lines = [];
        foreach ($legacyFields as $legacyId => $field) {
            $legacyId = (int) $legacyId;
            if ($legacyId < 1) {
                continue;
            }
            if ($column === 'obrigatorio') {
                $value = PeticaoModeloCampo::mapLegacyRequired($field['req'] ?? null) ? 1 : 0;
                $lines[] = 'UPDATE peticao_modelo_campos SET obrigatorio = ' . $value
                    . ' WHERE legacy_input_id = ' . $legacyId
                    . ' AND obrigatorio <> ' . $value
                    . ($overwrite ? ';' : ' AND (updated_at IS NULL OR created_at IS NULL OR updated_at <= created_at);');
            } else {
                $value = PeticaoModeloCampo::mapLegacyBehavior($field['alt'] ?? '');
                if ($value === '') {
                    continue;
                }
                $lines[] = "UPDATE peticao_modelo_campos SET comportamento = '" . $value . "'"
                    . ' WHERE legacy_input_id = ' . $legacyId
                    . ($overwrite ? ';' : " AND (comportamento IS NULL OR comportamento = '');");
            }
        }

        return implode("\n", $lines) . "\n";
    }
}
