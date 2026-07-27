<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $legacyGrupos = DB::table('tp_grupo_tb')->count();
        $normalizedGrupos = DB::table('lista_grupos')->count();
        $legacyItens = DB::table('tp_lista_tb')->count();
        $normalizedItens = DB::table('lista_itens')->count();
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_listas', false);

        $this->line('Listas');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Grupos', $legacyGrupos, $normalizedGrupos, $legacyGrupos === $normalizedGrupos ? 'OK' : 'DIVERGENTE'],
                ['Itens', $legacyItens, $normalizedItens, $legacyItens === $normalizedItens ? 'OK' : 'DIVERGENTE'],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }

    protected function auditSql(): void
    {
        $legacyConfigs = DB::table('tp_config_db')->count();
        $normalizedProfiles = DB::table('sql_server_profiles')->count();
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_sql_configs', false);
        $compatRoutesEnabled = (bool) config('legacy.compat_admin_sql_routes', true);

        $this->line('Servidores SQL');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Perfis', $legacyConfigs, $normalizedProfiles, $legacyConfigs === $normalizedProfiles ? 'OK' : 'DIVERGENTE'],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
                ['Rotas admin legacy', $compatRoutesEnabled ? 'ligadas' : 'desligadas', '-', $compatRoutesEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }
}
