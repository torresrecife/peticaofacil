<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoPrintViewTest extends TestCase
{
    public function test_user_can_open_clean_print_view_for_saved_peticao()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 705,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticoes')->insert([
            'id' => 34832,
            'modelo_id' => 705,
            'user_id' => $user->id,
            'codigo_externo' => '32216005',
            'nome_arquivo' => 'substabelecimento_teste',
            'cliente_referencia' => 'Cliente Teste',
            'conteudo_html' => '<p><strong>Conteudo de impressao</strong></p>',
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes-salvas/34832/impressao')
            ->assertStatus(200)
            ->assertSee('peticao-print-sheet', false)
            ->assertSee('Conteudo de impressao', false)
            ->assertSee('SUBSTABELECIMENTO');
    }
}
