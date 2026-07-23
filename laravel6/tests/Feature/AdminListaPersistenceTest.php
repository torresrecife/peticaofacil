<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminListaPersistenceTest extends TestCase
{
    public function test_edit_page_shows_items_as_rows_without_inline_inputs()
    {
        $this->ensureListaTables();

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'id_setor' => 7,
            'acesso_usu' => now(),
        ]);

        DB::table('tp_grupo_tb')->insert([
            'id_grupo' => 1,
            'nome_grupo' => 'Lista Teste',
            'data_cad' => '2026-01-10 10:00:00',
        ]);

        DB::table('tp_lista_tb')->insert([
            'id_lista' => 10,
            'id_grupo' => 1,
            'nome_lista' => 'Item A',
            'return_1' => 'A1',
            'return_2' => 'A2',
            'data_cad' => '2026-01-10 10:00:00',
            'id_setor' => 7,
        ]);

        $this->actingAs($admin)
            ->get('/admin/listas/1/edit')
            ->assertStatus(200)
            ->assertSee('Item #10')
            ->assertSee('Item A')
            ->assertSee('Retorno 1')
            ->assertDontSee('name="items[0][nome_lista]"', false)
            ->assertSee('/admin/listas/1/itens/10/edit', false);
    }

    public function test_item_is_updated_in_dedicated_edit_page()
    {
        $this->ensureListaTables();

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'id_setor' => 7,
            'acesso_usu' => now(),
        ]);

        DB::table('tp_grupo_tb')->insert([
            'id_grupo' => 1,
            'nome_grupo' => 'Lista Teste',
            'data_cad' => '2026-01-10 10:00:00',
        ]);

        DB::table('tp_lista_tb')->insert([
            'id_lista' => 10,
            'id_grupo' => 1,
            'nome_lista' => 'Item A',
            'return_1' => 'A1',
            'data_cad' => '2026-01-10 10:00:00',
            'id_setor' => 7,
        ]);

        $this->actingAs($admin)->put('/admin/listas/1/itens/10', [
            'nome_lista' => 'Item A Atualizado',
            'return_1' => 'A2',
            'return_2' => '',
            'return_3' => '',
            'return_4' => '',
            'return_5' => '',
            'return_6' => '',
            'id_setor' => 7,
        ])->assertRedirect('/admin/listas/1/edit');

        $item = DB::table('tp_lista_tb')->where('id_lista', 10)->first();
        $this->assertSame('Item A Atualizado', $item->nome_lista);
        $this->assertSame('A2', $item->return_1);
        $this->assertSame('2026-01-10 10:00:00', $item->data_cad);
    }

    public function test_edit_page_filters_items_by_search_term()
    {
        $this->ensureListaTables();

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'id_setor' => 7,
            'acesso_usu' => now(),
        ]);

        DB::table('tp_grupo_tb')->insert([
            'id_grupo' => 1,
            'nome_grupo' => 'Lista Teste',
            'data_cad' => '2026-01-10 10:00:00',
        ]);

        DB::table('tp_lista_tb')->insert([
            [
                'id_lista' => 10,
                'id_grupo' => 1,
                'nome_lista' => 'Alpha',
                'return_1' => 'Primeiro',
                'data_cad' => '2026-01-10 10:00:00',
                'id_setor' => 7,
            ],
            [
                'id_lista' => 11,
                'id_grupo' => 1,
                'nome_lista' => 'Beta',
                'return_1' => 'Segundo',
                'data_cad' => '2026-01-10 10:00:00',
                'id_setor' => 7,
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/listas/1/edit?search=Alpha')
            ->assertStatus(200)
            ->assertSee('Alpha')
            ->assertDontSee('Beta')
            ->assertSee('para "Alpha"');
    }

    protected function ensureListaTables()
    {
        if (!Schema::hasTable('tp_grupo_tb')) {
            Schema::create('tp_grupo_tb', function (Blueprint $table) {
                $table->increments('id_grupo');
                $table->string('nome_grupo', 500);
                $table->string('titulo_1', 500)->nullable();
                $table->string('titulo_2', 500)->nullable();
                $table->string('titulo_3', 500)->nullable();
                $table->string('titulo_4', 500)->nullable();
                $table->dateTime('data_cad')->nullable();
            });
        } else {
            DB::table('tp_grupo_tb')->truncate();
        }

        if (!Schema::hasTable('tp_lista_tb')) {
            Schema::create('tp_lista_tb', function (Blueprint $table) {
                $table->increments('id_lista');
                $table->unsignedInteger('id_grupo');
                $table->string('nome_lista', 500)->nullable();
                $table->string('return_1', 500)->nullable();
                $table->string('return_2', 500)->nullable();
                $table->string('return_3', 500)->nullable();
                $table->string('return_4', 500)->nullable();
                $table->string('return_5', 500)->nullable();
                $table->string('return_6', 500)->nullable();
                $table->dateTime('data_cad')->nullable();
                $table->unsignedInteger('id_setor')->nullable();
            });
        } else {
            DB::table('tp_lista_tb')->truncate();
        }
    }
}
