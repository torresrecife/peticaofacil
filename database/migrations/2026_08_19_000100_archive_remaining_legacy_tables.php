<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveRemainingLegacyTables extends Migration
{
    protected $tables = [
        'tp_dados_tb' => 'tp_dados_tb_archive_20260727',
        'tp_inputs_tb' => 'tp_inputs_tb_archive_20260727',
        'tp_funda_tb' => 'tp_funda_tb_archive_20260727',
        'tp_lista_tb' => 'tp_lista_tb_archive_20260727',
        'tp_grupo_tb' => 'tp_grupo_tb_archive_20260727',
        'tp_config_db' => 'tp_config_db_archive_20260727',
        'tp_tipo_tb' => 'tp_tipo_tb_archive_20260727',
        'tp_pecas_tb' => 'tp_pecas_tb_archive_20260727',
        'tp_usu_tb' => 'tp_usu_tb_archive_20260727',
    ];

    public function up()
    {
        $this->assertFullyMapped('tp_grupo_tb', 'id_grupo', 'lista_grupos', 'legacy_grupo_id');
        $this->assertFullyMapped('tp_lista_tb', 'id_lista', 'lista_itens', 'legacy_lista_id');
        $this->assertFullyMapped('tp_config_db', 'id_db', 'sql_server_profiles', 'legacy_config_id');
        $this->assertFullyMapped('tp_tipo_tb', 'tipo_id', 'peticao_modelos', 'legacy_tipo_id');
        $this->assertFullyMapped('tp_pecas_tb', 'id_pecas', 'peticoes', 'legacy_peca_id');
        $this->assertFullyMapped('tp_usu_tb', 'id_usu', 'users', 'legacy_usuario_id');

        // Residuos filhos sem pai normalizado sao historicos conhecidos. O corte
        // bloqueia apenas componentes ausentes que ainda pertencam a um pai valido.
        $this->assertMappedForNormalizedParent(
            'tp_funda_tb', 'fund_id', 'tipo_id',
            'peticao_modelos', 'legacy_tipo_id',
            'peticao_modelo_paragrafos', 'legacy_fund_id'
        );
        $this->assertMappedForNormalizedParent(
            'tp_inputs_tb', 'id_input', 'tipo_id',
            'peticao_modelos', 'legacy_tipo_id',
            'peticao_modelo_campos', 'legacy_input_id'
        );
        $this->assertMappedForNormalizedParent(
            'tp_dados_tb', 'id_dados', 'id_input',
            'peticao_modelo_campos', 'legacy_input_id',
            'peticao_modelo_campo_opcoes', 'legacy_dado_id'
        );

        foreach ($this->tables as $source => $archive) {
            if (!Schema::hasTable($source)) {
                continue;
            }

            if (Schema::hasTable($archive)) {
                throw new RuntimeException('Destino de arquivamento ja existe: ' . $archive);
            }

            Schema::rename($source, $archive);
        }
    }

    public function down()
    {
        foreach (array_reverse($this->tables, true) as $source => $archive) {
            if (!Schema::hasTable($source) && Schema::hasTable($archive)) {
                Schema::rename($archive, $source);
            }
        }
    }

    protected function assertFullyMapped($source, $sourceKey, $target, $targetLegacyKey)
    {
        if (!Schema::hasTable($source)) {
            return;
        }

        if (!Schema::hasTable($target)) {
            throw new RuntimeException('Tabela normalizada ausente: ' . $target);
        }

        $missing = DB::table($source . ' as legacy')
            ->leftJoin($target . ' as normalized', 'normalized.' . $targetLegacyKey, '=', 'legacy.' . $sourceKey)
            ->whereNull('normalized.' . $targetLegacyKey)
            ->count();

        if ($missing !== 0) {
            throw new RuntimeException("Arquivamento bloqueado: {$source} possui {$missing} registro(s) sem correspondente em {$target}.");
        }
    }

    protected function assertMappedForNormalizedParent(
        $source,
        $sourceKey,
        $sourceParentKey,
        $normalizedParent,
        $normalizedParentLegacyKey,
        $target,
        $targetLegacyKey
    ) {
        if (!Schema::hasTable($source)) {
            return;
        }

        if (!Schema::hasTable($normalizedParent) || !Schema::hasTable($target)) {
            throw new RuntimeException('Estrutura normalizada incompleta para arquivar ' . $source . '.');
        }

        $missing = DB::table($source . ' as legacy')
            ->join(
                $normalizedParent . ' as parent',
                'parent.' . $normalizedParentLegacyKey,
                '=',
                'legacy.' . $sourceParentKey
            )
            ->leftJoin($target . ' as normalized', 'normalized.' . $targetLegacyKey, '=', 'legacy.' . $sourceKey)
            ->whereNull('normalized.' . $targetLegacyKey)
            ->count();

        if ($missing !== 0) {
            throw new RuntimeException("Arquivamento bloqueado: {$source} possui {$missing} registro(s) de pais validos sem correspondente em {$target}.");
        }
    }
}
