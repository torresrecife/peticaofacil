<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeticaoSavedEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('peticao_modelos') || !Schema::hasTable('peticoes')) {
            $this->setUpLegacySchema();
        }
    }

    public function test_saved_peticao_editor_updates_normalized_record_and_exports()
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

        DB::table('peticoes')->insert([
            'id' => 4001,
            'legacy_peca_id' => null,
            'modelo_id' => 300,
            'user_id' => $user->id,
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
            ->assertSee('Revise a minuta, ajuste o texto final e salve antes de exportar.')
            ->assertSee('Filtrar historico');

        $this->actingAs($user)
            ->put('/peticoes-salvas/4001', [
            'nome_cli' => 'Cliente Atualizado',
            'cod_pecas' => '<p>Texto atualizado</p>',
        ])->assertRedirect('/peticoes-salvas/4001/editar');

        $normalized = DB::table('peticoes')->where('id', 4001)->first();
        $versions = DB::table('peticao_versoes')->where('peticao_id', 4001)->get();

        $this->assertSame('Cliente Atualizado', $normalized->cliente_referencia);
        $this->assertStringContainsString('Texto atualizado', $normalized->conteudo_html);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $this->assertCount(1, $versions);
        $this->assertSame('save', $versions[0]->origem_snapshot);

        $this->actingAs($user)
            ->post('/peticoes-salvas/4001/exportar/word', [
                'nome_cli' => 'Cliente Atualizado',
                'cod_pecas' => '<p>Texto atualizado</p>',
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $pdfResponse = $this->actingAs($user)
            ->post('/peticoes-salvas/4001/exportar/pdf', [
                'nome_cli' => 'Cliente Atualizado',
                'cod_pecas' => '<p>Texto atualizado</p>',
            ]);

        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());

        if (!Schema::hasTable('peticao_versoes')) {
            $this->createOrResetTable('peticao_versoes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('peticao_id');
                $table->unsignedInteger('versao_numero');
                $table->unsignedInteger('legacy_peca_id_snapshot')->nullable();
                $table->unsignedInteger('legacy_usuario_id_snapshot')->nullable();
                $table->unsignedBigInteger('user_id_snapshot')->nullable();
                $table->string('codigo_externo_snapshot', 255)->nullable();
                $table->string('cliente_referencia_snapshot', 500)->nullable();
                $table->longText('conteudo_html_snapshot');
                $table->text('campos_resolvidos_snapshot')->nullable();
                $table->string('origem_snapshot', 50)->default('save');
                $table->timestamp('criado_em')->nullable();
                $table->timestamps();
            });
        }

        DB::table('peticao_versoes')->insert([
            'peticao_id' => 4001,
            'versao_numero' => 2,
            'legacy_peca_id_snapshot' => null,
            'legacy_usuario_id_snapshot' => 30,
            'user_id_snapshot' => $user->id,
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
            ->post('/peticoes-salvas/4001/versoes/' . $restoreVersionId . '/exportar/word')
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $versionPdfResponse = $this->actingAs($user)
            ->post('/peticoes-salvas/4001/versoes/' . $restoreVersionId . '/exportar/pdf');
        $versionPdfResponse->assertStatus(200);
        $versionPdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $versionPdfResponse->getContent());

        $this->actingAs($user)
            ->post('/peticoes-salvas/4001/versoes/' . $restoreVersionId . '/restaurar')
            ->assertRedirect('/peticoes-salvas/4001/editar');

        $normalizedAfterRestore = DB::table('peticoes')->where('id', 4001)->first();
        $this->assertSame('Cliente Restaurado', $normalizedAfterRestore->cliente_referencia);
        $this->assertStringContainsString('Texto restaurado', $normalizedAfterRestore->conteudo_html);
        $historyDate = now()->format('Y-m-d');

        $this->actingAs($user)
            ->get('/peticoes-salvas/4001/editar?origin=restore&user_id=' . $user->id . '&date_from=' . $historyDate . '&date_to=' . $historyDate)
            ->assertStatus(200)
            ->assertSee('restore')
            ->assertSee('Cliente Restaurado')
            ->assertSee($historyDate)
            ->assertDontSee('Cliente Atualizado');
    }
}
