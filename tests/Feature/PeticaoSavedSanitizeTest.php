<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoSavedSanitizeTest extends TestCase
{
    private $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = factory(User::class)->create(['nivel_usu' => 'USU', 'id_setor' => 5, 'acesso_usu' => now()]);

        DB::table('peticao_modelos')->insert([
            'id' => 820, 'nome' => 'MODELO', 'slug' => 'modelo-sanitize', 'status' => 'ativo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticoes')->insert([
            'id' => 8201, 'modelo_id' => 820, 'user_id' => $this->owner->id, 'nome_arquivo' => 'peca',
            'cliente_referencia' => 'Cliente', 'conteudo_html' => '<p>original</p>',
            'gerado_em' => now(), 'salvo_em' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_saving_strips_scripts_and_handlers_but_keeps_the_document()
    {
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
        $payload = '<p>Peca <b>valida</b></p>'
            . '<script>fetch("/roubar")</script>'
            . '<img src="' . $png . '" onerror="alert(document.cookie)">'
            . '<a href="javascript:alert(1)">x</a>'
            . '<table><tr><td align="right">célula</td></tr></table>';

        $this->actingAs($this->owner)
            ->put('/peticoes-salvas/8201', ['nome_cli' => 'Cliente', 'cod_pecas' => $payload])
            ->assertRedirect();

        $stored = DB::table('peticoes')->where('id', 8201)->value('conteudo_html');

        // O perigoso sai.
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('fetch("/roubar")', $stored);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $stored);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $stored);
        // O documento permanece.
        $this->assertStringContainsString('<b>valida</b>', $stored);
        $this->assertStringContainsString('data:image/png;base64,', $stored);
        $this->assertStringContainsString('align="right"', $stored);
        $this->assertStringContainsString('célula', $stored);

        // A versao guardada no historico tambem fica limpa.
        $snapshot = DB::table('peticao_versoes')->where('peticao_id', 8201)->orderByDesc('id')->value('conteudo_html_snapshot');
        $this->assertStringNotContainsString('<script', $snapshot);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $snapshot);

        // Reabrir o editor nao reintroduz o script (o textarea escapa, e o conteudo ja esta limpo).
        $this->actingAs($this->owner)
            ->get('/peticoes-salvas/8201/editar')
            ->assertStatus(200)
            ->assertDontSee('<script>fetch', false)
            ->assertDontSee('onerror=', false);
    }
}
