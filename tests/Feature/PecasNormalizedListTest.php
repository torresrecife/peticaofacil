<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PecasNormalizedListTest extends TestCase
{
    public function test_saved_pieces_list_reads_from_normalized_table()
    {
        $admin = factory(User::class)->create([
            'id_usu' => 10,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 9,
            'nome_setor' => 'Civel',
            'cod_setor' => 'CIV',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 90,
            'legacy_tipo_id' => 90,
            'legacy_setor_id' => 9,
            'nome' => 'Modelo Normalizado',
            'slug' => 'modelo-normalizado-90',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 501,
            'tipo_id' => 90,
            'id_usu' => 10,
            'nome_pecas' => 'Modelo Normalizado',
            'nome_cli' => 'Cliente X',
            'cod_pecas' => '<p>Teste</p>',
            'data_cad' => now(),
            'cod_sav' => 'ABC123',
        ]);

        DB::table('peticoes')->insert([
            'id' => 1001,
            'legacy_peca_id' => 501,
            'modelo_id' => 90,
            'user_id' => $admin->id,
            'legacy_usuario_id' => 10,
            'codigo_externo' => 'ABC123',
            'nome_arquivo' => 'Modelo Normalizado',
            'cliente_referencia' => 'Cliente X',
            'conteudo_html' => '<p>Teste</p>',
            'campos_resolvidos' => null,
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/pecas')
            ->assertStatus(200)
            ->assertSee('Cliente X')
            ->assertSee('Modelo Normalizado')
            ->assertSee(route('peticoes.saved.edit', 1001), false);
    }
}
