<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\Services\PeticaoAssistantStateService;
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
        $page->assertSee('name="field_value"', false);
        $page->assertSee('id="assistant-current-field-select"', false);

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

    public function test_assistant_shows_search_input_for_large_pending_select_lists()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_select_busca',
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
            'legacy_tipo_id' => 60,
            'nome' => 'MODELO COM LISTA GRANDE',
            'slug' => 'modelo-lista-grande',
            'status' => 'ativo',
        ]);

        $campo = $modelo->campos()->create([
            'legacy_input_id' => 3001,
            'rotulo' => 'OAB/UF',
            'token' => '@campo3001@',
            'tipo' => 'SELECT',
            'origem_coluna' => '',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
        ]);

        foreach (['AC', 'AL', 'AM', 'BA', 'CE', 'DF', 'PE'] as $index => $uf) {
            $campo->opcoes()->create([
                'rotulo' => $uf,
                'valor_retorno' => 'OAB/' . $uf . ' ' . (1000 + $index),
                'ordem' => $index + 1,
            ]);
        }

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '8100000-11.2026.8.26.0100',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '8100000-11.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $page = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $page->assertSee('Digite para filtrar ou selecionar automaticamente');
        $page->assertSee('js-assistant-option-search', false);
        $page->assertSee('A lista e longa. Digite parte do texto ou do retorno para filtrar.');

        $this->actingAs($user)->post(route('peticoes.assistente.answer-current-field'), [
            'field_query' => 'OAB/PE',
        ]);

        $pageAfterTextAnswer = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $pageAfterTextAnswer->assertSee('PE');
        $pageAfterTextAnswer->assertSee('OAB/PE 1006');
        $pageAfterTextAnswer->assertSee('Abrir montagem assistida');
    }

    public function test_assistant_prioritizes_fields_inside_explicit_group_before_ungrouped_required_fields()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_grupos',
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
            'legacy_tipo_id' => 61,
            'nome' => 'MODELO COM BLOCOS',
            'slug' => 'modelo-com-blocos',
            'status' => 'ativo',
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 5001,
            'rotulo' => 'CODIGO INTERNO',
            'token' => '@campo5001@',
            'tipo' => 'INPUT',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 5002,
            'rotulo' => 'DADOS DO AUTOR',
            'token' => '',
            'tipo' => 'TITLE',
            'obrigatorio' => false,
            'visivel' => true,
            'ordem' => 2,
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 5003,
            'rotulo' => 'NOME DO AUTOR',
            'token' => '@campo5003@',
            'tipo' => 'INPUT',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 3,
        ]);

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '8200000-11.2026.8.26.0100',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '8200000-11.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $state = app(PeticaoAssistantStateService::class)->current();
        $this->assertSame('campo_5003', $state['current_pending_field']['field_key']);
        $this->assertSame('DADOS DO AUTOR', $state['current_pending_field']['group_label']);
    }

    public function test_assistant_applies_deterministic_values_before_asking_required_fields()
    {
        config()->set('openai.api_key', null);

        $user = factory(User::class)->create([
            'login_usu' => 'assistente_derivado',
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
            'legacy_tipo_id' => 62,
            'nome' => 'MODELO DERIVADO',
            'slug' => 'modelo-derivado',
            'status' => 'ativo',
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 6001,
            'rotulo' => 'CIDADE DO FORO',
            'token' => '@campo6001@',
            'tipo' => 'INPUT',
            'valor_padrao' => 'Recife',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 1,
        ]);

        $campo = $modelo->campos()->create([
            'legacy_input_id' => 6002,
            'rotulo' => 'OAB/UF',
            'token' => '@campo6002@',
            'tipo' => 'SELECT',
            'obrigatorio' => true,
            'visivel' => true,
            'ordem' => 2,
            'eventos_frontend' => [
                'focus' => 'fc_ajax_comp("tp_lista_tb","return_1","campo6003","unir","id_lista",this,1);',
            ],
        ]);

        $modelo->campos()->create([
            'legacy_input_id' => 6003,
            'rotulo' => 'RETORNO - OAB/UF',
            'token' => '@campo6003@',
            'tipo' => 'INPUT',
            'obrigatorio' => false,
            'visivel' => true,
            'ordem' => 3,
        ]);

        $campo->opcoes()->create([
            'rotulo' => 'PE',
            'valor_retorno' => 'OAB/PE 21.678',
            'ordem' => 1,
        ]);

        $lookup = \Mockery::mock(SqlServerLookupService::class);
        $lookup->shouldReceive('connectionStatus')->andReturn([
            'available' => true,
            'server_name' => 'NEO',
            'message' => null,
        ]);
        $lookup->shouldReceive('fetchByCode')->andReturn([
            'PROCESSO' => '8300000-11.2026.8.26.0100',
        ]);
        $this->app->instance(SqlServerLookupService::class, $lookup);

        $this->actingAs($user)->post(route('peticoes.assistente.message'), [
            'message' => '8300000-11.2026.8.26.0100',
        ]);

        $this->actingAs($user)->post(route('peticoes.assistente.select-model', $modelo));

        $state = app(PeticaoAssistantStateService::class)->current();
        $this->assertSame('ready_for_handoff', $state['conversation_stage']);
        $this->assertNull($state['current_pending_field']);
        $this->assertSame('Recife', $state['assistant_field_answers']['campo_6001']['value']);
        $this->assertSame('PE', $state['assistant_field_answers']['campo_6002']['value']);
        $this->assertSame('OAB/PE 21.678', $state['assistant_field_answers']['campo_6003']['value']);

        $page = $this->actingAs($user)->get(route('peticoes.assistente.index'));
        $page->assertDontSee('Campo em coleta');
        $page->assertSee('Respostas ja confirmadas');
        $page->assertSee('Recife');
        $page->assertSee('PE');
        $page->assertSee('OAB/PE 21.678');
        $page->assertSee('Abrir montagem assistida');
    }
}
