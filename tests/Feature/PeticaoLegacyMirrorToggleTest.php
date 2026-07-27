<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoLegacyMirrorToggleTest extends TestCase
{
    public function test_normalized_editor_save_works_without_legacy_piece_mirroring()
    {
        config()->set('legacy.mirror_legacy_pecas', false);

        $user = factory(User::class)->create([
            'id_usu' => 77,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 77,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 77,
            'tipo_nome' => 'Modelo Sem Espelho',
            'id_setor' => 77,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 77,
            'legacy_tipo_id' => 77,
            'legacy_setor_id' => 77,
            'nome' => 'Modelo Sem Espelho',
            'slug' => 'modelo-sem-espelho-77',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/peticoes/modelos/77/salvar', [
            'nome_cli' => 'Cliente Sem Legado',
            'cod_pecas' => '<p>Somente normalizado</p>',
        ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 77)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }

    public function test_normalized_editor_routes_work_without_legacy_tipo_id()
    {
        config()->set('legacy.mirror_legacy_pecas', false);

        $user = factory(User::class)->create([
            'id_usu' => 78,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 78,
            'legacy_tipo_id' => null,
            'legacy_setor_id' => null,
            'nome' => 'Modelo Puro',
            'slug' => 'modelo-puro-78',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/peticoes/modelos/78/editor', [
                'nome_cli' => 'Cliente Puro',
                'content' => '<p>Fluxo puro</p>',
            ])
            ->assertStatus(200)
            ->assertSee('Editor final da peca');

        $response = $this->actingAs($user)
            ->post('/peticoes/modelos/78/salvar', [
                'nome_cli' => 'Cliente Puro',
                'cod_pecas' => '<p>Fluxo puro</p>',
            ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 78)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }

    public function test_legacy_save_route_uses_normalized_path_when_model_has_mirror_and_legacy_mirror_is_disabled()
    {
        config()->set('legacy.mirror_legacy_pecas', false);
        config()->set('legacy.compat_public_model_routes', true);

        $user = factory(User::class)->create([
            'id_usu' => 79,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 79,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 79,
            'tipo_nome' => 'Modelo Legado Migrado',
            'id_setor' => 79,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 79,
            'legacy_tipo_id' => 79,
            'legacy_setor_id' => 79,
            'nome' => 'Modelo Legado Migrado',
            'slug' => 'modelo-legado-migrado-79',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/peticoes/79/salvar', [
            'nome_cli' => 'Cliente via rota antiga',
            'cod_pecas' => '<p>Persistencia normalizada</p>',
        ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 79)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }
}
