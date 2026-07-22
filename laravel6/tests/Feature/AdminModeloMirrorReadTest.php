<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminModeloMirrorReadTest extends TestCase
{
    public function test_modelos_index_and_edit_show_normalized_read_data()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 7,
            'nome_setor' => 'Fiscal',
            'cod_setor' => 'FIS',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 77,
            'tipo_nome' => 'Modelo Lido do Mirror',
            'id_setor' => 7,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 77,
            'legacy_tipo_id' => 77,
            'legacy_setor_id' => 7,
            'nome' => 'Modelo Lido do Mirror',
            'slug' => 'modelo-lido-do-mirror-77',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            'modelo_id' => 77,
            'legacy_fund_id' => 700,
            'titulo' => 'Intro',
            'conteudo_html' => '<p>Intro</p>',
            'ordem' => 1,
            'visivel' => 1,
            'ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'modelo_id' => 77,
            'legacy_input_id' => 701,
            'rotulo' => 'Campo A',
            'token' => '@campo701@',
            'tipo' => 'TEXT',
            'ordem' => 1,
            'obrigatorio' => 0,
            'visivel' => 1,
            'gera_nome_arquivo' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos')
            ->assertStatus(200)
            ->assertSee('modelo-lido-do-mirror-77')
            ->assertSee('1 paragrafos, 1 campos');

        $this->actingAs($admin)
            ->get('/admin/modelos/77/edit')
            ->assertRedirect('/admin/modelos-normalizados/77/edit');

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados/77/edit')
            ->assertStatus(200)
            ->assertSee('Leitura normalizada')
            ->assertSee('modelo-lido-do-mirror-77');
    }
}
