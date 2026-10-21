<?php

namespace App\Console\Commands;

use App\PeticaoModeloCampo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige o "obrigatorio" dos campos migrados, que veio invertido.
 *
 * No sistema antigo tp_inputs_tb.input_req = 2 e obrigatorio e 1 nao e
 * (PeticaoModeloCampo::mapLegacyRequired). A migracao gravou o contrario:
 * campos obrigatorios ficaram opcionais e vice-versa.
 *
 * Casa por legacy_input_id. Campos editados no sistema novo depois da
 * migracao (updated_at > created_at) sao mantidos: o valor deles foi
 * escolhido por alguem na tela. Use --overwrite para corrigi-los tambem.
 *
 * Por seguranca o padrao e somente relatorio (dry-run); use --apply para gravar.
 */
class BackfillCampoObrigatorio extends Command
{
    protected $signature = 'peticao:backfill-obrigatorio
        {--apply : Grava as alteracoes (sem esta flag apenas relata)}
        {--overwrite : Corrige tambem campos editados no sistema novo depois da migracao}
        {--modelo=* : Limita a um ou mais tipo_id legados}
        {--sql= : Em vez de gravar, escreve os UPDATEs num arquivo .sql}';

    protected $description = 'Corrige o campo obrigatorio (invertido na migracao) a partir do input_req do sistema antigo.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $overwrite = (bool) $this->option('overwrite');
        $modelos = array_filter(array_map('intval', (array) $this->option('modelo')));
        $sqlPath = $this->option('sql');

        try {
            $query = DB::connection('legacy_mysql')->table('tp_inputs_tb')
                ->select('id_input', 'tipo_id', 'input_req');
            if ($modelos) {
                $query->whereIn('tipo_id', $modelos);
            }
            $legacyRows = $query->get()->keyBy('id_input');
        } catch (\Throwable $e) {
            $this->error('Nao foi possivel ler o banco antigo (conexao legacy_mysql / peticaofacil_old).');
            $this->line('Configure LEGACY_DB_* no .env e rode na maquina que enxerga o banco antigo.');
            $this->line('Detalhe: ' . $e->getMessage());
            return 1;
        }

        $this->info('Lidos ' . count($legacyRows) . ' campos do sistema antigo.');

        $stats = [
            'ja_ok' => 0,
            'a_corrigir' => 0,
            'editados_mantidos' => 0,
            'sem_campo_novo' => 0,
        ];
        $porValor = ['passa_a_obrigatorio' => 0, 'passa_a_opcional' => 0];
        $updates = [];   // [campo_id => 0|1]
        $exemplos = [];

        $campos = DB::table('peticao_modelo_campos')
            ->whereIn('legacy_input_id', $legacyRows->keys()->all())
            ->get(['id', 'legacy_input_id', 'rotulo', 'obrigatorio', 'created_at', 'updated_at'])
            ->keyBy('legacy_input_id');

        foreach ($legacyRows as $legacyId => $row) {
            $campo = $campos[$legacyId] ?? null;
            if (!$campo) {
                $stats['sem_campo_novo']++;
                continue;
            }

            $esperado = PeticaoModeloCampo::mapLegacyRequired($row->input_req) ? 1 : 0;
            if ((int) $campo->obrigatorio === $esperado) {
                $stats['ja_ok']++;
                continue;
            }

            $editado = $campo->updated_at && $campo->created_at && $campo->updated_at > $campo->created_at;
            if ($editado && !$overwrite) {
                $stats['editados_mantidos']++;
                if (count($exemplos) < 20) {
                    $exemplos[] = "campo {$campo->id} (legacy {$legacyId}) {$campo->rotulo}: editado em {$campo->updated_at} (mantido; use --overwrite)";
                }
                continue;
            }

            $stats['a_corrigir']++;
            $porValor[$esperado ? 'passa_a_obrigatorio' : 'passa_a_opcional']++;
            $updates[$campo->id] = $esperado;
        }

        $this->line('');
        $this->info('Resumo:');
        foreach ($stats + $porValor as $k => $v) {
            $this->line(sprintf('  %-22s %d', $k, $v));
        }
        if ($exemplos) {
            $this->line('');
            $this->warn('Campos editados no sistema novo (mantidos):');
            foreach ($exemplos as $ex) {
                $this->line('  ' . $ex);
            }
        }

        $total = count($updates);
        $this->line('');
        $this->info("Campos a atualizar: {$total}");

        if ($sqlPath) {
            $lines = [];
            foreach ($updates as $campoId => $valor) {
                $lines[] = 'UPDATE peticao_modelo_campos SET obrigatorio = ' . (int) $valor . ' WHERE id = ' . (int) $campoId . ';';
            }
            file_put_contents($sqlPath, implode("\n", $lines) . "\n");
            $this->info("Script SQL gravado em: {$sqlPath}");
            return 0;
        }

        if (!$apply) {
            $this->comment('Modo relatorio (dry-run). Rode com --apply para gravar.');
            return 0;
        }

        // Sem tocar em updated_at: o campo continua contando como "nao editado na tela".
        $afetados = 0;
        DB::transaction(function () use ($updates, &$afetados) {
            foreach ([1, 0] as $valor) {
                $ids = array_keys(array_filter($updates, function ($v) use ($valor) {
                    return $v === $valor;
                }));
                foreach (array_chunk($ids, 500) as $chunk) {
                    $afetados += DB::table('peticao_modelo_campos')
                        ->whereIn('id', $chunk)
                        ->update(['obrigatorio' => $valor]);
                }
            }
        });

        $this->info("Gravado. Linhas atualizadas: {$afetados}.");
        return 0;
    }
}
