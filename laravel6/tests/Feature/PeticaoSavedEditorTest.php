<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoSavedEditorTest extends TestCase
{
    public function test_saved_peticao_editor_updates_normalized_and_legacy_records_and_exports()
    {
        $user = factory(User::class)->create([
            'id_usu' => 30,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 30,
            'nome_setor' => 'Trabalhista',
            'cod_setor' => 'TRA',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 300,
            'tipo_nome' => 'Modelo Persistido',
            'id_setor' => 30,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 300,
            'legacy_tipo_id' => 300,
            'legacy_setor_id' => 30,
            'nome' => 'Modelo Persistido',
            'slug' => 'modelo-persistido-300',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 3001,
            'tipo_id' => 300,
            'id_usu' => 30,
            'nome_pecas' => 'Modelo Persistido',
            'nome_cli' => 'Cliente Original',
            'cod_pecas' => '<p>Texto original</p>',
            'data_cad' => now(),
            'cod_sav' => 'P3001',
        ]);

        DB::table('peticoes')->insert([
            'id' => 4001,
            'legacy_peca_id' => 3001,
            'modelo_id' => 300,
            'legacy_usuario_id' => 30,
            'codigo_externo' => 'P3001',
            'nome_arquivo' => 'Modelo Persistido',
            'cliente_referencia' => 'Cliente Original',
            'conteudo_html' => '<p>Texto original</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes-salvas/4001/editar')
            ->assertStatus(200)
            ->assertSee('Editor de peticao salva')
            ->assertSee('Entidade principal: `peticoes`.', false)
            ->assertSee('Filtrar historico');

        $this->actingAs($user)
            ->put('/peticoes-salvas/4001', [
                'nome_cli' => 'Cliente Atualizado',
                'cod_pecas' => '<p>Texto atualizado</p>',
            ])->assertRedirect('/peticoes-salvas/4001/editar');

        $legacy = DB::table('tp_pecas_tb')->where('id_pecas', 3001)->first();
        $normalized = DB::table('peticoes')->where('id', 4001)->first();
        $versions = DB::table('peticao_versoes')->where('peticao_id', 4001)->get();

        $this->assertSame('Cliente Atualizado', $legacy->nome_cli);
        $this->assertSame('Cliente Atualizado', $normalized->cliente_referencia);
        $this->assertStringContainsString('Texto atualizado', $legacy->cod_pecas);
        $this->assertStringContainsString('Texto atualizado', $normalized->conteudo_html);
        $this->assertCount(1, $versions);
        $this->assertSame('save', $versions[0]->origem_snapshot);

        $this->actingAs($user)
            ->post('/peticoes-salvas/4001/exportar/word', [
                'nome_cli' => 'Cliente Atualizado',
                'cod_pecas' => '<p>Texto atualizado</p>',
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/msword; charset=UTF-8');

        $pdfResponse = $this->actingAs($user)
            ->post('/peticoes-salvas/4001/exportar/pdf', [
                'nome_cli' => 'Cliente Atualizado',
                'cod_pecas' => '<p>Texto atualizado</p>',
            ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());

        $versionId = DB::table('peticao_versoes')->where('peticao_id', 4001)->value('id');

        $this->actingAs($user)
            ->get('/peticoes-salvas/4001/versoes/' . $versionId . '/comparar')
            ->assertStatus(200)
            ->assertSee('Comparacao de versoes')
            ->assertSee('Texto atualizado')
            ->assertSee('diff-changed', false);

        DB::table('peticao_versoes')->insert([
            'peticao_id' => 4001,
            'versao_numero' => 2,
            'legacy_peca_id_snapshot' => 3001,
            'legacy_usuario_id_snapshot' => 30,
            'codigo_externo_snapshot' => 'P3001',
            'cliente_referencia_snapshot' => 'Cliente Restaurado',
            'conteudo_html_snapshot' => '<p>Texto restaurado</p>',
            'campos_resolvidos_snapshot' => null,
            'origem_snapshot' => 'manual',
            'criado_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $restoreVersionId = DB::table('peticao_versoes')
            ->where('peticao_id', 4001)
            ->where('versao_numero', 2)
            ->value('id');

        $this->actingAs($user)
            ->post('/peticoes-salvas/4001/versoes/' . $restoreVersionId . '/restaurar')
            ->assertRedirect('/peticoes-salvas/4001/editar');

        $normalizedAfterRestore = DB::table('peticoes')->where('id', 4001)->first();
        $this->assertSame('Cliente Restaurado', $normalizedAfterRestore->cliente_referencia);
        $this->assertStringContainsString('Texto restaurado', $normalizedAfterRestore->conteudo_html);

        $this->actingAs($user)
            ->get('/peticoes-salvas/4001/editar?origin=restore')
            ->assertStatus(200)
            ->assertSee('restore')
            ->assertSee('Cliente Restaurado')
            ->assertDontSee('Cliente Atualizado');
    }
}
