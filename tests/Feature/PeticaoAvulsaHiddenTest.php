<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoAvulsaHiddenTest extends TestCase
{
    private $admin;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        $this->user = factory(User::class)->create(['nivel_usu' => 'USU', 'acesso_usu' => now()]);

        DB::table('setores')->insert(['id_setor' => 1, 'nome_setor' => 'Jurídico', 'cod_setor' => 'JUR', 'data_cad' => now()]);
        // Dois inserts separados: linhas com colunas diferentes num insert em lote
        // fazem o Laravel alinhar pelas colunas da primeira linha e desalinhar o resto.
        DB::table('peticao_modelos')->insert([
            'id' => 900, 'legacy_tipo_id' => 900, 'legacy_setor_id' => 1, 'nome' => 'MODELO COMUM',
            'slug' => 'modelo-comum', 'status' => 'ativo', 'arquivo_padrao' => 'pdf', 'metadata' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticao_modelos')->insert([
            'id' => 901, 'legacy_tipo_id' => null, 'legacy_setor_id' => 1, 'nome' => 'Peticao avulsa',
            'slug' => '__peticao-avulsa__', 'status' => 'ativo', 'arquivo_padrao' => 'doc',
            'metadata' => json_encode(['system' => 'avulsa']), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_is_system_flag_and_scope()
    {
        $this->assertTrue(PeticaoModelo::find(901)->is_system);
        $this->assertFalse(PeticaoModelo::find(900)->is_system);

        $ids = PeticaoModelo::withoutSystem()->pluck('id')->all();
        $this->assertContains(900, $ids);
        $this->assertNotContains(901, $ids);
    }

    public function test_admin_model_list_hides_the_system_model()
    {
        $html = $this->actingAs($this->admin)->get('/admin/modelos-normalizados')->assertStatus(200)->getContent();

        $this->assertStringContainsString('MODELO COMUM', $html);
        $this->assertStringNotContainsString('Peticao avulsa', $html);
        $this->assertStringNotContainsString('__peticao-avulsa__', $html);
    }

    public function test_assembly_model_list_hides_the_system_model()
    {
        $html = $this->actingAs($this->user)->get('/peticoes')->assertStatus(200)->getContent();

        $this->assertStringContainsString('MODELO COMUM', $html);
        $this->assertStringNotContainsString('__peticao-avulsa__', $html);
    }

    public function test_editing_the_system_model_redirects_to_its_own_screen()
    {
        $this->actingAs($this->admin)
            ->get('/admin/modelos-normalizados/901/edit')
            ->assertRedirect(route('admin.peticoes-avulsas.config.edit'));

        $this->actingAs($this->admin)
            ->put('/admin/modelos-normalizados/901', ['tipo_nome' => 'Hack', 'id_setor' => 1, 'tipo_stt' => 'Y', 'tipo_arq' => 'pdf'])
            ->assertRedirect(route('admin.peticoes-avulsas.config.edit'));

        // O modelo comum continua editável.
        $this->actingAs($this->admin)->get('/admin/modelos-normalizados/900/edit')->assertStatus(200);
    }

    public function test_exporting_the_system_model_is_not_found()
    {
        $this->actingAs($this->admin)->get('/admin/modelos-normalizados/901/exportar')->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/modelos-normalizados/900/exportar')->assertStatus(200);
    }

    public function test_assembling_the_system_model_redirects_to_the_avulsa_flow()
    {
        $this->actingAs($this->user)
            ->get('/peticoes/modelos/901')
            ->assertRedirect(route('peticoes.avulsas.create'));

        $this->actingAs($this->user)->get('/peticoes/modelos/900')->assertStatus(200);
    }
}
