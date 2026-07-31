<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoProcessCodePersistenceTest extends TestCase
{
    public function test_process_code_is_persisted_when_creating_draft_from_preview()
    {
        $user = factory(User::class)->create([
            'login_usu' => 'persistencia',
            'password' => bcrypt('secret'),
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $modelo = PeticaoModelo::create([
            'legacy_tipo_id' => 48,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento',
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($user)->post('/peticoes/modelos/' . $modelo->id . '/peticao-normalizada', [
            'nome_cli' => 'Cliente Draft',
            'content' => '<p>Teste</p>',
            'codigo_processo' => '5001234-55.2026.8.26.0100',
        ]);

        $location = $response->headers->get('Location');
        preg_match('#/peticoes-salvas/(\d+)/editar#', (string) $location, $matches);
        $peticaoId = isset($matches[1]) ? (int) $matches[1] : null;
        $peticao = $peticaoId ? DB::table('peticoes')->where('id', $peticaoId)->first() : null;

        $this->assertNotNull($peticao);
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
        $this->assertSame('5001234-55.2026.8.26.0100', $peticao->codigo_externo);
    }

    public function test_process_code_is_persisted_when_saving_new_editor_petition()
    {
        $user = factory(User::class)->create([
            'login_usu' => 'persistencia2',
            'password' => bcrypt('secret'),
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $modelo = PeticaoModelo::create([
            'legacy_tipo_id' => 49,
            'nome' => 'CONTRAMINUTA',
            'slug' => 'contraminuta',
            'status' => 'ativo',
        ]);

        $response = $this->actingAs($user)->post('/peticoes/modelos/' . $modelo->id . '/salvar', [
            'nome_cli' => 'Cliente Editor',
            'cod_pecas' => '<p>Conteudo</p>',
            'codigo_processo' => '6000000-11.2026.8.26.0100',
        ]);

        $peticao = DB::table('peticoes')->where('cliente_referencia', 'Cliente Editor')->first();

        $this->assertNotNull($peticao);
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
        $this->assertSame('6000000-11.2026.8.26.0100', $peticao->codigo_externo);
    }
}
