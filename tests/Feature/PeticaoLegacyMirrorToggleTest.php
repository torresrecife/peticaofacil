<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeticaoLegacyMirrorToggleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('peticao_modelos') || !Schema::hasTable('peticoes')) {
            $this->setUpLegacySchema();
        }
    }

    public function test_normalized_editor_save_persists_only_normalized_piece()
    {
        $user = factory(User::class)->create([
            'id_usu' => 77,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->seedModeloMinimo(77, 77, 'Modelo Sem Espelho');

        $response = $this->actingAs($user)->post('/peticoes/modelos/77/salvar', [
            'nome_cli' => 'Cliente Sem Legado',
            'cod_pecas' => '<p>Somente normalizado</p>',
        ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 77)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }

    public function test_normalized_editor_routes_work_without_legacy_tipo_id()
    {
        $user = factory(User::class)->create([
            'id_usu' => 78,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->seedModeloMinimo(78, null, 'Modelo Puro');

        $this->actingAs($user)
            ->post('/peticoes/modelos/78/editor', [
                'nome_cli' => 'Cliente Puro',
                'content' => '<p>Fluxo puro</p>',
            ])
            ->assertStatus(200)
            ->assertSee('Editor final da peca');

        $response = $this->actingAs($user)
            ->post('/peticoes/modelos/78/salvar', [
                'nome_cli' => 'Cliente Puro',
                'cod_pecas' => '<p>Fluxo puro</p>',
            ]);

        $peticao = DB::table('peticoes')->where('modelo_id', 78)->first();

        $this->assertNotNull($peticao);
        $this->assertNull($peticao->legacy_peca_id);
        $this->assertSame(0, DB::table('tp_pecas_tb')->count());
        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');
    }

    public function test_legacy_save_route_is_not_available_anymore()
    {
        $user = factory(User::class)->create([
            'id_usu' => 79,
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $response = $this->actingAs($user)->post('/peticoes/79/salvar', [
            'nome_cli' => 'Cliente via rota antiga',
            'cod_pecas' => '<p>Persistencia normalizada</p>',
        ]);
        $response->assertStatus(404);
    }

    protected function seedModeloMinimo(int $id, ?int $legacyTipoId, string $nome): void
    {
        DB::table('setores')->insert([
            'id_setor' => $id,
            'nome_setor' => 'Setor ' . $id,
            'cod_setor' => 'S' . $id,
            'data_cad' => now(),
        ]);

        DB::table('clientes')->insert([
            'cliente_id' => $id,
            'cliente_name' => 'Cliente ' . $id,
            'cliente_cod' => 'CLI' . $id,
            'cliente_area' => 1,
            'cliente_status' => 'Y',
            'cliente_creator' => now(),
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => $id,
            'legacy_tipo_id' => $legacyTipoId,
            'legacy_cliente_id' => $id,
            'legacy_setor_id' => $id,
            'legacy_sql_config_id' => null,
            'nome' => $nome,
            'slug' => strtolower(str_replace(' ', '-', $nome)) . '-' . $id,
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'cabecalho_html' => '<p>Cabecalho</p>',
            'rodape_html' => '<p>Rodape</p>',
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
