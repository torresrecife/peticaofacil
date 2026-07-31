<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\SqlServerLookupService;
use App\SqlServerProfile;
use App\User;
use Tests\TestCase;

class PeticaoAssistantFlowTest extends TestCase
{
    public function test_assistant_loads_process_and_suggests_models()
    {
        $user = factory(User::class)->create([
            'login_usu' => 'assistente',
            'password' => bcrypt('secret'),
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $profile = SqlServerProfile::create([
            'nome' => 'NEO',
            'host' => '127.0.0.1',
            'database_name' => 'externo',
            'username' => 'sa',
            'password' => '123',
            'status' => 'ativo',
        ]);

        $modelo = PeticaoModelo::create([
            'legacy_tipo_id' => 48,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento',
            'status' => 'ativo',
        ]);

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '5001234-55.2026.8.26.0100',
            'AUTOR' => 'Cliente Teste',
            'COMARCA' => 'Sao Paulo',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $response = $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '5001234-55.2026.8.26.0100',
        ]);

        $response->assertRedirect(route('peticoes.assistente.index'));

        $page = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $page->assertSee('Cliente Teste');
        $page->assertSee('SUBSTABELECIMENTO');
        $page->assertSee('Qual peticao voce quer elaborar');
        $page->assertDontSee('Abrir montagem assistida');

        $select = $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));
        $select->assertRedirect(route('peticoes.assistente.index'));

        $pageAfterSelect = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $pageAfterSelect->assertSee('Abrir montagem assistida');
        $pageAfterSelect->assertSee('5001234-55.2026.8.26.0100');
    }
}
