<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\SqlServerLookupService;
use App\SqlServerProfile;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoAssistantConflictAndJurisprudenciaTest extends TestCase
{
    public function test_assistant_displays_conflict_alerts_and_jurisprudencia_sources()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_conflicto',
            'password' => bcrypt('secret'),
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        SqlServerProfile::create([
            'nome' => 'NEO',
            'host' => '127.0.0.1',
            'database_name' => 'externo',
            'username' => 'sa',
            'password' => '123',
            'status' => 'ativo',
        ]);

        $modelo = PeticaoModelo::create([
            'legacy_tipo_id' => 50,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento',
            'status' => 'ativo',
        ]);

        $modelo->campos()->create([
            'rotulo' => 'AUTOR',
            'token' => '@campo2001@',
            'tipo' => 'INPUT',
            'origem_coluna' => 'AUTOR',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
        ]);

        DB::table('peticoes')->insert([
            'modelo_id' => $modelo->id,
            'user_id' => $user->id,
            'codigo_externo' => '7000000-11.2026.8.26.0100',
            'nome_arquivo' => 'Teste',
            'cliente_referencia' => 'Cliente Teste',
            'conteudo_html' => '<p>Teste</p>',
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '7000000-11.2026.8.26.0100',
            'AUTOR' => 'Cliente Teste',
            'COMARCA' => 'Sao Paulo',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '7000000-11.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $response = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $response->assertSee('Jurisprudencia sugerida');
        $response->assertSee('STF - Pesquisa de Jurisprudencia');
        $response->assertSee('STJ - Pesquisa de Jurisprudencia');
        $response->assertSee('Ja existe peticao com o mesmo processo e o mesmo modelo.');
        $response->assertSee('O mesmo cliente ja recebeu este modelo nos ultimos 30 dias.');
    }
}
