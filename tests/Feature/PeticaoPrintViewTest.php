<?php

namespace Tests\Feature;

use App\Services\PeticaoExportService;
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
            ->assertSee('peticao-print-content', false)
            ->assertSee('padding: 64px', false)
            ->assertSee('Conteudo de impressao', false);
    }

    public function test_export_rendering_preserves_intentional_spacing_in_right_aligned_header_blocks()
    {
        $service = app(PeticaoExportService::class);
        $imagePath = str_replace('\\', '/', public_path('img/add.png'));
        $html = '<table><tr><td><img src="' . $imagePath . '"></td><td>'
            . '<p style="text-align: right;">&nbsp;&nbsp;OAB  PE  21.678&nbsp;&nbsp;</p>'
            . '</td></tr></table>';

        $printView = $service->renderPrintView('teste', $html, [], 'browser')->render();
        $wordView = $service->renderWordDocument('teste', $html);

        $this->assertStringContainsString('white-space: normal;', $printView);
        $this->assertStringContainsString('OAB  PE  21.678', $printView);
        $this->assertStringNotContainsString('&nbsp;&nbsp;OAB', $printView);
        $this->assertStringContainsString('class="print-header-contact"', $printView);
        $this->assertSame('PK', substr($wordView, 0, 2));
    }
}
