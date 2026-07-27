<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyCutReadiness extends Command
{
    protected $signature = 'legacy:cut-readiness {--module=all : all|listas|sql}';
    protected $description = 'Audita a prontidao para o corte das tabelas legadas de listas e SQL';

    public function handle()
    {
        $module = strtolower((string) $this->option('module'));

        if (!in_array($module, ['all', 'listas', 'sql'], true)) {
            $this->error('Modulo invalido. Use all, listas ou sql.');

            return 1;
        }

        if ($module === 'all' || $module === 'listas') {
            $this->auditListas();
        }

        if ($module === 'all' || $module === 'sql') {
            $this->auditSql();
        }

        return 0;
    }

    protected function auditListas(): void
    {
        $legacyGruposExists = Schema::hasTable('tp_grupo_tb');
        $legacyItensExists = Schema::hasTable('tp_lista_tb');

        $legacyGrupos = $legacyGruposExists ? DB::table('tp_grupo_tb')->count() : 'arquivada';
        $normalizedGrupos = Schema::hasTable('lista_grupos') ? DB::table('lista_grupos')->count() : 'ausente';
        $legacyItens = $legacyItensExists ? DB::table('tp_lista_tb')->count() : 'arquivada';
        $normalizedItens = Schema::hasTable('lista_itens') ? DB::table('lista_itens')->count() : 'ausente';
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_listas', false);

        $this->line('Listas');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Grupos', $legacyGrupos, $normalizedGrupos, $this->statusForPair($legacyGrupos, $normalizedGrupos)],
                ['Itens', $legacyItens, $normalizedItens, $this->statusForPair($legacyItens, $normalizedItens)],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }

    protected function auditSql(): void
    {
        $legacyConfigs = Schema::hasTable('tp_config_db') ? DB::table('tp_config_db')->count() : 'arquivada';
        $normalizedProfiles = Schema::hasTable('sql_server_profiles') ? DB::table('sql_server_profiles')->count() : 'ausente';
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_sql_configs', false);
        $compatRoutesEnabled = (bool) config('legacy.compat_admin_sql_routes', true);

        $this->line('Servidores SQL');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Perfis', $legacyConfigs, $normalizedProfiles, $this->statusForPair($legacyConfigs, $normalizedProfiles)],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
                ['Rotas admin legacy', $compatRoutesEnabled ? 'ligadas' : 'desligadas', '-', $compatRoutesEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }

    protected function statusForPair($legacyValue, $normalizedValue): string
    {
        if ($legacyValue === 'arquivada' && $normalizedValue !== 'ausente') {
            return 'ARQUIVADA';
        }

        return $legacyValue === $normalizedValue ? 'OK' : 'DIVERGENTE';
    }
}
