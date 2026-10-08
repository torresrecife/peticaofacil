<?php

namespace App\Console\Commands;

use App\PeticaoModeloCampo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recupera as mascaras de campo (comportamento) que nao vieram na migracao.
 *
 * O sistema antigo guarda a mascara em tp_inputs_tb.input_alt
 * (date, decimal, integer, cpf, cnpj, cpf_cnpj, cep, phone, processo...).
 * A migracao trouxe parte (date/decimal/integer) e deixou outras de fora,
 * entao campos de CPF/CNPJ/CEP/telefone ficaram sem mascara na montagem.
 *
 * Este comando le o input_alt do banco antigo (conexao legacy_mysql),
 * mapeia para o conjunto suportado e preenche peticao_modelo_campos.comportamento
 * casando por legacy_input_id.
 *
 * Por seguranca o padrao e somente relatorio (dry-run); use --apply para gravar.
 */
class BackfillCampoComportamento extends Command
{
    protected $signature = 'peticao:backfill-comportamento
        {--apply : Grava as alteracoes (sem esta flag apenas relata)}
        {--overwrite : Sobrescreve comportamento ja preenchido quando divergir do legado}
        {--modelo=* : Limita a um ou mais tipo_id legados}
        {--sql= : Em vez de gravar, escreve os UPDATEs num arquivo .sql}';

    protected $description = 'Recupera as mascaras de campo (comportamento) a partir do input_alt do sistema antigo.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $overwrite = (bool) $this->option('overwrite');
        $modelos = array_filter(array_map('intval', (array) $this->option('modelo')));
        $sqlPath = $this->option('sql');

        try {
            $query = DB::connection('legacy_mysql')->table('tp_inputs_tb')
                ->select('id_input', 'tipo_id', 'input_alt');
            if ($modelos) {
                $query->whereIn('tipo_id', $modelos);
            }
            $legacyRows = $query->get();
        } catch (\Throwable $e) {
            $this->error('Nao foi possivel ler o banco antigo (conexao legacy_mysql / peticaofacil_old).');
            $this->line('Configure LEGACY_DB_* no .env e rode na maquina que enxerga o banco antigo.');
            $this->line('Detalhe: ' . $e->getMessage());
            return 1;
        }

        $this->info('Lidos ' . count($legacyRows) . ' campos do sistema antigo.');

        $stats = [
            'preenchidos' => 0,   // vazio -> mascara
            'ja_ok' => 0,         // ja igual ao legado
            'divergentes' => 0,   // novo tem X, legado tem Y
            'sem_mascara_legado' => 0,
            'sem_campo_novo' => 0,
            'sobrescritos' => 0,
        ];
        $porMascara = [];
        $updates = [];   // [campo_id => comportamento]
        $sqlLines = [];
        $exemplos = [];

        foreach ($legacyRows as $row) {
            $legacyBehavior = PeticaoModeloCampo::mapLegacyBehavior($row->input_alt);
            if ($legacyBehavior === '') {
                $stats['sem_mascara_legado']++;
                continue;
            }

            $campo = PeticaoModeloCampo::where('legacy_input_id', $row->id_input)->first();
            if (!$campo) {
                $stats['sem_campo_novo']++;
                continue;
            }

            $atual = strtolower(trim((string) $campo->getAttributes()['comportamento'] ?? ''));

            if ($atual === $legacyBehavior) {
                $stats['ja_ok']++;
                continue;
            }

            if ($atual === '') {
                $stats['preenchidos']++;
            } else {
                $stats['divergentes']++;
                if (!$overwrite) {
                    if (count($exemplos) < 20) {
                        $exemplos[] = "campo {$campo->id} (legacy {$row->id_input}): novo='{$atual}' legado='{$legacyBehavior}' (mantido; use --overwrite)";
                    }
                    continue;
                }
                $stats['sobrescritos']++;
            }

            $porMascara[$legacyBehavior] = ($porMascara[$legacyBehavior] ?? 0) + 1;
            $updates[$campo->id] = $legacyBehavior;
            $sqlLines[] = "UPDATE peticao_modelo_campos SET comportamento = '" . $legacyBehavior . "' WHERE id = " . (int) $campo->id . ";";
        }

        $this->line('');
        $this->info('Resumo:');
        foreach ($stats as $k => $v) {
            $this->line(sprintf('  %-20s %d', $k, $v));
        }
        if ($porMascara) {
            $this->line('');
            $this->info('Mascaras a aplicar (por tipo):');
            foreach ($porMascara as $m => $n) {
                $this->line(sprintf('  %-12s %d', $m, $n));
            }
        }
        if ($exemplos) {
            $this->line('');
            $this->warn('Divergencias mantidas (sem --overwrite):');
            foreach ($exemplos as $ex) {
                $this->line('  ' . $ex);
            }
        }

        $total = count($updates);
        $this->line('');
        $this->info("Campos a atualizar: {$total}");

        if ($sqlPath) {
            file_put_contents($sqlPath, implode("\n", $sqlLines) . "\n");
            $this->info("Script SQL gravado em: {$sqlPath}");
            return 0;
        }

        if (!$apply) {
            $this->comment('Modo relatorio (dry-run). Rode com --apply para gravar.');
            return 0;
        }

        $afetados = 0;
        DB::transaction(function () use ($updates, &$afetados) {
            foreach ($updates as $campoId => $comportamento) {
                $afetados += DB::table('peticao_modelo_campos')
                    ->where('id', $campoId)
                    ->update(['comportamento' => $comportamento]);
            }
        });

        $this->info("Gravado. Linhas atualizadas: {$afetados}.");
        return 0;
    }
}
