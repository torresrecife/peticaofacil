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
}
