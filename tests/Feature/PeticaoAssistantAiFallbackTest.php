<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\SqlServerProfile;
use App\User;
use App\Services\SqlServerLookupService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoAssistantAiFallbackTest extends TestCase
{
    public function test_assistant_displays_missing_fields_and_duplicate_checks_without_openai_key()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_fallback',
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
            'legacy_tipo_id' => 48,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento',
            'status' => 'ativo',
        ]);

        $modelo->campos()->create([
            'rotulo' => 'NOME DO AUTOR',
            'token' => '@campo1001@',
            'tipo' => 'INPUT',
            'origem_coluna' => 'AUTOR',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
        ]);

        $modelo->campos()->create([
            'rotulo' => 'CPF DO AUTOR',
            'token' => '@campo1002@',
            'tipo' => 'INPUT',
            'origem_coluna' => 'CPF',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 2,
        ]);

        DB::table('peticoes')->insert([
            'modelo_id' => $modelo->id,
            'user_id' => $user->id,
            'codigo_externo' => '5001234-55.2026.8.26.0100',
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
            'PROCESSO' => '5001234-55.2026.8.26.0100',
            'AUTOR' => 'Cliente Teste',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '5001234-55.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $response = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $response->assertSee('Campos faltantes');
        $response->assertSee('CPF DO AUTOR');
        $response->assertSee('Campo em coleta');
        $response->assertSee('Possivel duplicidade');
        $response->assertSee('Mesmo codigo de processo');
        $response->assertSee('orientacao local do sistema');
        $response->assertDontSee('Abrir montagem assistida');
    }
}
