<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyCutReadiness extends Command
{
    protected $signature = 'legacy:cut-readiness {--module=all : all|listas|sql|modelos|pecas|users}';
    protected $description = 'Audita a prontidao para o corte das tabelas legadas de listas e SQL';

    public function handle()
    {
        $module = strtolower((string) $this->option('module'));

        if (!in_array($module, ['all', 'listas', 'sql', 'modelos', 'pecas', 'users'], true)) {
            $this->error('Modulo invalido. Use all, listas, sql, modelos, pecas ou users.');

            return 1;
        }

        if ($module === 'all' || $module === 'listas') {
            $this->auditListas();
        }

        if ($module === 'all' || $module === 'sql') {
            $this->auditSql();
        }

        if ($module === 'all' || $module === 'modelos') {
            $this->auditModelos();
        }

        if ($module === 'all' || $module === 'pecas') {
            $this->auditPecas();
        }

        if ($module === 'all' || $module === 'users') {
            $this->auditUsers();
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

    protected function auditModelos(): void
    {
        $legacyTipos = Schema::hasTable('tp_tipo_tb') ? DB::table('tp_tipo_tb')->count() : 'arquivada';
        $legacyParagrafos = Schema::hasTable('tp_funda_tb') ? DB::table('tp_funda_tb')->count() : 'arquivada';
        $legacyCampos = Schema::hasTable('tp_inputs_tb') ? DB::table('tp_inputs_tb')->count() : 'arquivada';
        $legacyDados = Schema::hasTable('tp_dados_tb') ? DB::table('tp_dados_tb')->count() : 'arquivada';
        $normalizedModelos = Schema::hasTable('peticao_modelos') ? DB::table('peticao_modelos')->count() : 'ausente';
        $normalizedParagrafos = Schema::hasTable('peticao_modelo_paragrafos') ? DB::table('peticao_modelo_paragrafos')->count() : 'ausente';
        $normalizedCampos = Schema::hasTable('peticao_modelo_campos') ? DB::table('peticao_modelo_campos')->count() : 'ausente';
        $normalizedDados = Schema::hasTable('peticao_modelo_campo_opcoes') ? DB::table('peticao_modelo_campo_opcoes')->count() : 'ausente';
        $compatRoutesEnabled = (bool) config('legacy.compat_admin_model_routes', true);

        $this->line('Modelos');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Modelos', $legacyTipos, $normalizedModelos, $this->statusForPair($legacyTipos, $normalizedModelos)],
                ['Paragrafos', $legacyParagrafos, $normalizedParagrafos, $this->statusForPair($legacyParagrafos, $normalizedParagrafos)],
                ['Campos', $legacyCampos, $normalizedCampos, $this->statusForPair($legacyCampos, $normalizedCampos)],
                ['Opcoes', $legacyDados, $normalizedDados, $this->statusForPair($legacyDados, $normalizedDados)],
                ['Rotas admin legacy', $compatRoutesEnabled ? 'ligadas' : 'desligadas', '-', $compatRoutesEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }

    protected function auditPecas(): void
    {
        $legacyPecas = Schema::hasTable('tp_pecas_tb') ? DB::table('tp_pecas_tb')->count() : 'arquivada';
        $normalizedPecas = Schema::hasTable('peticoes') ? DB::table('peticoes')->count() : 'ausente';
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_pecas', false);
        $compatRouteEnabled = (bool) config('legacy.compat_public_piece_editor_route', true);

        $this->line('Pecas');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Historico', $legacyPecas, $normalizedPecas, $this->statusForPair($legacyPecas, $normalizedPecas)],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
                ['Rota editor legacy', $compatRouteEnabled ? 'ligada' : 'desligada', '-', $compatRouteEnabled ? 'PENDENTE' : 'OK'],
            ]
        );
    }

    protected function auditUsers(): void
    {
        $legacyUsers = Schema::hasTable('tp_usu_tb') ? DB::table('tp_usu_tb')->count() : 'arquivada';
        $normalizedUsers = Schema::hasTable('users') ? DB::table('users')->count() : 'ausente';
        $mirrorEnabled = (bool) config('legacy.mirror_legacy_users', false);
        $authFallbackEnabled = (bool) config('legacy.auth_fallback_legacy_users', false);

        $this->line('Usuarios');
        $this->table(
            ['Item', 'Legado', 'Normalizado', 'Status'],
            [
                ['Usuarios', $legacyUsers, $normalizedUsers, $this->statusForPair($legacyUsers, $normalizedUsers)],
                ['Mirror legado', $mirrorEnabled ? 'ligado' : 'desligado', '-', $mirrorEnabled ? 'PENDENTE' : 'OK'],
                ['Auth fallback', $authFallbackEnabled ? 'ligado' : 'desligado', '-', $authFallbackEnabled ? 'PENDENTE' : 'OK'],
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
