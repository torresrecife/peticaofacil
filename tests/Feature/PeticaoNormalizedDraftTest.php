<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoNormalizedDraftTest extends TestCase
{
    public function test_preview_can_create_normalized_peticao_without_legacy_origin_and_version_history()
    {
        $user = factory(User::class)->create([
            'id_usu' => 77,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('setores')->insert([
            'id_setor' => 11,
            'nome_setor' => 'Administrativo',
            'cod_setor' => 'ADM',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 11,
            'legacy_tipo_id' => 11,
            'legacy_setor_id' => 11,
            'nome' => 'Modelo Draft',
            'slug' => 'modelo-draft-11',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post('/peticoes/modelos/11/peticao-normalizada', [
                'nome_cli' => 'Cliente Draft',
                'content' => '<p>Conteudo draft</p>',
                'resolved_fields' => json_encode(['campo_1' => 'valor']),
            ]);

        $peticao = DB::table('peticoes')->where('cliente_referencia', 'Cliente Draft')->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');

        $this->actingAs($user)
            ->put('/peticoes-salvas/' . $peticao->id, [
                'nome_cli' => 'Cliente Draft Atualizado',
                'cod_pecas' => '<p>Conteudo draft atualizado</p>',
            ])->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');

        $draftAtualizado = DB::table('peticoes')->where('id', $peticao->id)->first();
        $this->assertNull($draftAtualizado->legacy_peca_id);
        $this->assertSame('Cliente Draft Atualizado', $draftAtualizado->cliente_referencia);

        $versoes = DB::table('peticao_versoes')->where('peticao_id', $peticao->id)->orderBy('versao_numero')->get();
        $this->assertCount(2, $versoes);
        $this->assertSame('draft', $versoes[0]->origem_snapshot);
        $this->assertSame('save', $versoes[1]->origem_snapshot);
    }
}
