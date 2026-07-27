<?php

namespace App\Console\Commands;

use App\Peca;
use App\Services\LegacyModeloSyncService;
use App\Services\LegacyPecaSyncService;
use App\PeticaoModelo;
use Illuminate\Console\Command;

class SyncLegacyPecas extends Command
{
    protected $signature = 'peticao:sync-pecas {peca_id? : ID legado da peca} {--year= : Ano das pecas a sincronizar} {--all : Sincroniza todo o historico legado} {--prepare-modelos : Sincroniza os modelos legados antes das pecas} {--chunk=100 : Quantidade por lote} {--limit= : Limite maximo de pecas a processar} {--from-id= : ID inicial legado para retomar o processamento}';

    protected $description = 'Sincroniza pecas legadas para a tabela normalizada peticoes.';

    public function handle(LegacyPecaSyncService $syncService, LegacyModeloSyncService $modeloSyncService)
    {
        $pecaId = $this->argument('peca_id');

        if ($pecaId) {
            $peca = Peca::with('tipo')->findOrFail($pecaId);
            $peticao = $syncService->syncPeca($peca, $peca->tipo);

            if (!$peticao) {
                $this->warn('Peca sem modelo normalizado correspondente.');

                return 1;
            }

            $this->info('Peca sincronizada: #' . $peca->id_pecas . ' -> #' . $peticao->id);

            return 0;
        }

        $year = $this->option('all')
            ? null
            : (int) ($this->option('year') ?: now()->year);
        $chunkSize = max(1, (int) $this->option('chunk'));
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $fromId = $this->option('from-id') !== null ? max(1, (int) $this->option('from-id')) : null;

        if ($this->option('prepare-modelos')) {
            $modelos = $modeloSyncService->syncAll();
            $this->info('Modelos sincronizados antes das pecas: ' . $modelos);
        }

        $total = $syncService->countForYear($year);
        $syncable = $syncService->countSyncableForYear($year);

        if (PeticaoModelo::count() === 0) {
            $this->warn('Nenhum modelo espelhado encontrado em peticao_modelos.');
            $this->warn('Execute: php artisan peticao:sync-legado');

            return 1;
        }

        if ($syncable === 0) {
            $scope = $year !== null ? ' no ano ' . $year : '';
            $this->warn('Nao ha pecas sincronizaveis' . $scope . '.');
            $this->warn('Total legado encontrado: ' . $total . '. Com modelo espelhado: 0.');

            return 1;
        }

        $scopeText = $year !== null ? ' do ano ' . $year : '';
        $this->info('Pecas legado encontradas' . $scopeText . ': ' . $total);
        $this->info('Pecas sincronizaveis' . $scopeText . ': ' . $syncable);
        $this->info('Lote: ' . $chunkSize . ($limit ? ' | Limite: ' . $limit : '') . ($fromId ? ' | A partir do ID: ' . $fromId : ''));

        $count = $syncService->syncAll($year, $chunkSize, $limit, $fromId, function ($processed, $synced, $lastId) {
            $this->line('Processadas: ' . $processed . ' | Sincronizadas: ' . $synced . ' | Ultimo ID: ' . $lastId);
        });

        if ($year !== null) {
            $this->info('Pecas sincronizadas de ' . $year . ': ' . $count);
        } else {
            $this->info('Pecas sincronizadas: ' . $count);
        }

        return 0;
    }
}
