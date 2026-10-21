<?php

namespace App\Console\Commands;

use App\Services\LegacyCampoRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige o "obrigatorio" dos campos migrados, que veio invertido.
 *
 * No sistema antigo tp_inputs_tb.input_req = 2 e obrigatorio e 1 nao e
 * (PeticaoModeloCampo::mapLegacyRequired). A migracao gravou o contrario.
 *
 * A migration 2026_10_20_000100 ja faz esta correcao no deploy, lendo
 * database/data/legacy-campos.json. Este comando serve para relatorio e para
 * rodar de novo contra o banco antigo (--snapshot le o arquivo em vez dele).
 *
 * Campos editados no sistema novo depois da migracao (updated_at > created_at)
 * sao mantidos; use --overwrite para corrigi-los tambem.
 * Por seguranca o padrao e somente relatorio (dry-run); use --apply para gravar.
 */
class BackfillCampoObrigatorio extends Command
{
    protected $signature = 'peticao:backfill-obrigatorio
        {--apply : Grava as alteracoes (sem esta flag apenas relata)}
        {--overwrite : Corrige tambem campos editados no sistema novo depois da migracao}
        {--modelo=* : Limita a um ou mais tipo_id legados (so com o banco antigo)}
        {--snapshot : Le database/data/legacy-campos.json em vez do banco antigo}
        {--sql= : Em vez de gravar, escreve UPDATEs portaveis (por legacy_input_id) num arquivo .sql}';

    protected $description = 'Corrige o campo obrigatorio (invertido na migracao) a partir do input_req do sistema antigo.';

    public function handle(LegacyCampoRestoreService $service)
    {
        $fields = $this->legacyFields($service);
        if ($fields === null) {
            return 1;
        }

        $this->info('Lidos ' . count($fields) . ' campos do sistema antigo.');

        if ($sqlPath = $this->option('sql')) {
            file_put_contents($sqlPath, $service->toSql($fields, 'obrigatorio', (bool) $this->option('overwrite')));
            $this->info("Script SQL gravado em: {$sqlPath}");
            return 0;
        }

        $plan = $service->planRequired($fields, (bool) $this->option('overwrite'));
        $values = array_count_values(array_map('strval', $plan['updates']));

        $this->line('');
        $this->info('Resumo:');
        foreach (['ja_ok', 'a_corrigir', 'editados_mantidos', 'sem_campo_novo'] as $key) {
            $this->line(sprintf('  %-22s %d', $key, $plan[$key]));
        }
        $this->line(sprintf('  %-22s %d', 'passa_a_obrigatorio', $values['1'] ?? 0));
        $this->line(sprintf('  %-22s %d', 'passa_a_opcional', $values['0'] ?? 0));

        if ($plan['editados']) {
            $this->line('');
            $this->warn('Campos editados no sistema novo (mantidos; use --overwrite):');
            foreach (array_slice($plan['editados'], 0, 20) as $campo) {
                $this->line("  campo {$campo->id} (legacy {$campo->legacy_input_id}) {$campo->rotulo}: editado em {$campo->updated_at}");
            }
        }

        $this->line('');
        $this->info('Campos a atualizar: ' . count($plan['updates']));

        if (!$this->option('apply')) {
            $this->comment('Modo relatorio (dry-run). Rode com --apply para gravar.');
            return 0;
        }

        $this->info('Gravado. Linhas atualizadas: ' . $service->apply($plan['updates'], 'obrigatorio') . '.');
        return 0;
    }

    protected function legacyFields(LegacyCampoRestoreService $service)
    {
        if ($this->option('snapshot')) {
            return $service->loadSnapshot();
        }

        try {
            $query = DB::connection('legacy_mysql')->table('tp_inputs_tb')->select('id_input', 'input_req', 'input_alt');
            $modelos = array_filter(array_map('intval', (array) $this->option('modelo')));
            if ($modelos) {
                $query->whereIn('tipo_id', $modelos);
            }

            $fields = [];
            foreach ($query->get() as $row) {
                $fields[(string) $row->id_input] = ['req' => (int) $row->input_req, 'alt' => (string) $row->input_alt];
            }

            return $fields;
        } catch (\Throwable $e) {
            $this->error('Nao foi possivel ler o banco antigo (conexao legacy_mysql / peticaofacil_old).');
            $this->line('Use --snapshot para ler database/data/legacy-campos.json.');
            $this->line('Detalhe: ' . $e->getMessage());
            return null;
        }
    }
}
