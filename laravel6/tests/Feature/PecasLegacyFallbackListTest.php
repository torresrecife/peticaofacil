<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PecasLegacyFallbackListTest extends TestCase
{
    public function test_saved_pieces_list_falls_back_to_legacy_table_when_normalized_list_is_empty()
    {
        $admin = factory(User::class)->create([
            'id_usu' => 20,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 19,
            'nome_setor' => 'Fallback',
            'cod_setor' => 'FBK',
            'data_cad' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 190,
            'tipo_nome' => 'Modelo Legado Puro',
            'id_setor' => 19,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('tp_pecas_tb')->insert([
            'id_pecas' => 1901,
            'tipo_id' => 190,
            'id_usu' => 20,
            'nome_pecas' => 'Modelo Legado Puro',
            'nome_cli' => 'Cliente Fallback',
            'cod_pecas' => '<p>Fallback</p>',
            'data_cad' => now(),
            'cod_sav' => 'FBK1901',
        ]);

        $this->actingAs($admin)
            ->get('/pecas')
            ->assertStatus(200)
            ->assertSee('Leitura de compatibilidade ativa.')
            ->assertSee('Cliente Fallback')
            ->assertSee('Modelo Legado Puro')
            ->assertSee('Legada')
            ->assertSee(route('peticoes.editor.edit', 1901), false);
    }
}
