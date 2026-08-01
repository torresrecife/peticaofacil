<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoDateFieldBehaviorTest extends TestCase
{
    public function test_mounting_renders_frontend_date_event_attributes_for_text_field()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 1,
            'nome_setor' => 'Juridico',
            'cod_setor' => 'JUR',
            'data_cad' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 70,
            'legacy_tipo_id' => 70,
            'legacy_setor_id' => 1,
            'nome' => 'MODELO DATA',
            'slug' => 'modelo-data-70',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_campos')->insert([
            'id' => 7001,
            'modelo_id' => 70,
            'legacy_input_id' => 7001,
            'rotulo' => 'DATA DO DOCUMENTO',
            'token' => '@campo7001@',
            'tipo' => 'TEXT',
            'comportamento' => 'date',
            'ordem' => 1,
            'colunas_layout' => 1,
            'linhas_layout' => 0,
            'visivel' => 1,
            'obrigatorio' => 1,
            'gera_nome_arquivo' => 0,
            'eventos_frontend' => json_encode([
                'load' => 'data_atual(this);',
                'blur' => 'data_extenso_out(this);',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/peticoes/modelos/70');

        $response->assertStatus(200)
            ->assertSee('class="js-frontend-event-field"', false)
            ->assertSee('data-event-load="data_atual(this);"', false)
            ->assertSee('data-event-blur="data_extenso_out(this);"', false)
            ->assertSee('data-input-behavior="date"', false);
    }
}
