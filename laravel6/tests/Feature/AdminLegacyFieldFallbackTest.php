<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminLegacyFieldFallbackTest extends TestCase
{
    public function test_legacy_paragraph_and_field_fallback_still_work_without_mirror()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 31,
            'nome_setor' => 'Seguros',
            'cod_setor' => 'SEG',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 310,
            'tipo_nome' => 'Modelo Campo Legado',
            'id_setor' => 31,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        $this->actingAs($admin)
            ->post('/admin/modelos/310/paragrafos', [
                'fund_titulo' => 'Paragrafo Fallback',
                'fund_text' => '<p>Texto legado puro</p>',
            ])
            ->assertRedirect('/admin/modelos-normalizados/1/edit');

        $this->actingAs($admin)
            ->post('/admin/modelos/310/campos', [
                'input_title' => 'Campo Fallback',
                'input_tipo' => 'SELECT',
                'input_pre' => '',
                'input_pos' => '',
                'input_db' => '',
                'input_val' => 'COLUNA_Z',
                'input_alt' => '',
                'input_cols' => 1,
                'input_rols' => 0,
                'input_focu' => '',
                'input_load' => '',
                'input_blur' => '',
                'input_width' => 265,
                'input_req' => 1,
                'input_order' => 1,
                'nomepet' => 'N',
                'hide' => 'true',
                'texto_padrao' => '',
                'add_class' => '',
                'opcoes' => "Sim|SIM\nNao|NAO",
            ])
            ->assertRedirect('/admin/modelos-normalizados/1/edit');

        $legacyParagrafo = DB::table('tp_funda_tb')->where('tipo_id', 310)->first();
        $legacyCampo = DB::table('tp_inputs_tb')->where('tipo_id', 310)->first();
        $legacyOpcoes = DB::table('tp_dados_tb')->where('id_input', $legacyCampo->id_input)->orderBy('dados_order')->get();
        $mirror = DB::table('peticao_modelos')->where('legacy_tipo_id', 310)->first();

        $this->assertSame('PARAGRAFO FALLBACK', $legacyParagrafo->fund_titulo);
        $this->assertSame('Campo Fallback', $legacyCampo->input_title);
        $this->assertCount(2, $legacyOpcoes);
        $this->assertNotNull($mirror);
    }
}
