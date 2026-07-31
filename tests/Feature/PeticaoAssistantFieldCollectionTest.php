<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\SqlServerLookupService;
use App\SqlServerProfile;
use App\User;
use Tests\TestCase;

class PeticaoAssistantFieldCollectionTest extends TestCase
{
    public function test_assistant_collects_missing_field_and_sends_it_to_mounting()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_campos',
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
            'cabecalho_html' => '<p>@campo1001@</p>',
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 1001,
            'rotulo' => 'CPF DO AUTOR',
            'token' => '@campo1001@',
            'tipo' => 'INPUT',
            'origem_coluna' => 'CPF',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
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

        $page = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $page->assertSee('Campo em coleta');
        $page->assertSee('CPF DO AUTOR');
        $page->assertDontSee('Abrir montagem assistida');

        $this->actingAs($user)->post(route('peticoes.assistente.answer-current-field'), [
            'field_value' => '123.456.789-00',
        ]);

        $pageAfterAnswer = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $pageAfterAnswer->assertSee('Respostas ja confirmadas');
        $pageAfterAnswer->assertSee('123.456.789-00');
        $pageAfterAnswer->assertSee('Abrir montagem assistida');

        $mount = $this->actingAs($user)->post(route('peticoes.normalized.compose', $modelo), [
            'action_type' => 'lookup',
            'codigo_processo' => '5001234-55.2026.8.26.0100',
            'assistant_resolved_fields' => json_encode([
                'campo_1001' => '123.456.789-00',
            ]),
        ]);

        $mount->assertStatus(200);
        $mount->assertSee('value="123.456.789-00"', false);
    }

    public function test_assistant_renders_select_for_pending_list_field_and_accepts_index_answer()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_select',
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
            'legacy_tipo_id' => 49,
            'nome' => 'SUBSTABELECIMENTO',
            'slug' => 'substabelecimento-select',
            'status' => 'ativo',
            'cabecalho_html' => '<p>@campo2001@</p><p>@campo2002@</p>',
        ]);

        $campo = $modelo->campos()->create([
            'legacy_input_id' => 2001,
            'rotulo' => 'NOME DO AUTOR',
            'token' => '@campo2001@',
            'tipo' => 'SELECT',
            'origem_coluna' => '',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
            'eventos_frontend' => [
                'focus' => 'fc_ajax_comp("tp_lista_tb","return_1","campo2002","unir","id_lista",this,1);',
            ],
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 2002,
            'rotulo' => 'RETORNO - OAB/UF',
            'token' => '@campo2002@',
            'tipo' => 'INPUT',
            'origem_coluna' => '',
            'obrigatorio' => false,
            'visivel' => true,
            'ordem' => 2,
        ]);

        $campo->opcoes()->create([
            'rotulo' => 'PE',
            'valor_retorno' => 'OAB/PE 21.678',
            'ordem' => 1,
        ]);

        $campo->opcoes()->create([
            'rotulo' => 'SP',
            'valor_retorno' => 'OAB/SP 98.765',
            'ordem' => 2,
        ]);

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '8000000-11.2026.8.26.0100',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '8000000-11.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $page = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $page->assertSee('Responder NOME DO AUTOR');
        $page->assertSee('1. PE');
        $page->assertSee('2. SP');
        $page->assertSee('RETORNO - OAB/UF');
        $page->assertSee('OAB/PE 21.678');
        $page->assertSee('<select name="field_value">', false);

        $this->actingAs($user)->post(route('peticoes.assistente.answer-current-field'), [
            'field_value' => '1',
        ]);

        $pageAfterAnswer = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $pageAfterAnswer->assertSee('PE');
        $pageAfterAnswer->assertSee('OAB/PE 21.678');
        $pageAfterAnswer->assertSee('Abrir montagem assistida');

        $mount = $this->actingAs($user)->post(route('peticoes.normalized.compose', $modelo), [
            'action_type' => 'preview',
            'codigo_processo' => '8000000-11.2026.8.26.0100',
            'assistant_resolved_fields' => json_encode([
                'campo_2001' => 'PE',
                'campo_2002' => 'OAB/PE 21.678',
            ]),
        ]);

        $mount->assertStatus(200);
        $mount->assertSee('PE', false);
        $mount->assertSee('OAB/PE 21.678', false);
    }
}
