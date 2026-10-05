<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeticaoSavedAccessTest extends TestCase
{
    private $owner;
    private $colleague;
    private $manager;
    private $otherManager;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Mesma senha para todos: os testes trocam de usuario na mesma sessao, e o
        // AuthenticateSession derruba a sessao quando o hash da senha muda.
        $hash = Hash::make('secret');
        $make = function ($level, $sector) use ($hash) {
            return factory(User::class)->create([
                'nivel_usu' => $level,
                'id_setor' => $sector,
                'password' => $hash,
                'senha_usu' => $hash,
            ]);
        };

        $this->owner = $make('USU', 5);
        $this->colleague = $make('USU', 5);
        $this->manager = $make('GER', 5);
        $this->otherManager = $make('GER', 6);
        $this->admin = $make('ADM', null);

        DB::table('peticao_modelos')->insert([
            'id' => 810,
            'nome' => 'MODELO DE ACESSO',
            'slug' => 'modelo-de-acesso',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticoes')->insert([
            'id' => 8101,
            'modelo_id' => 810,
            'user_id' => $this->owner->id,
            'codigo_externo' => '555001',
            'nome_arquivo' => 'peca_restrita',
            'cliente_referencia' => 'Cliente Restrito',
            'conteudo_html' => '<p>Conteudo original restrito</p>',
            'gerado_em' => now(),
            'salvo_em' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_owner_manager_of_the_sector_and_admin_can_open_the_peticao()
    {
        foreach ([$this->owner, $this->manager, $this->admin] as $user) {
            $this->actingAs($user)
                ->get('/peticoes-salvas/8101/impressao')
                ->assertStatus(200)
                ->assertSee('Conteudo original restrito', false);
        }
    }

    public function test_colleague_and_manager_of_another_sector_are_forbidden()
    {
        foreach ([$this->colleague, $this->otherManager] as $user) {
            $this->actingAs($user)->get('/peticoes-salvas/8101/impressao')->assertStatus(403);
            $this->actingAs($user)->get('/peticoes-salvas/8101/editar')->assertStatus(403);
        }
    }

    public function test_forbidden_user_cannot_change_or_export_the_peticao()
    {
        $payload = ['nome_cli' => 'Cliente Alterado', 'cod_pecas' => '<p>Conteudo alterado</p>'];

        $this->actingAs($this->colleague)->put('/peticoes-salvas/8101', $payload)->assertStatus(403);
        $this->actingAs($this->colleague)->post('/peticoes-salvas/8101/exportar/word', $payload)->assertStatus(403);
        $this->actingAs($this->colleague)->post('/peticoes-salvas/8101/exportar/pdf', $payload)->assertStatus(403);
        $this->actingAs($this->colleague)
            ->postJson('/peticoes-salvas/8101/revisar', ['cod_pecas' => '<p>x</p>'])
            ->assertStatus(403);

        $this->assertSame('Cliente Restrito', DB::table('peticoes')->where('id', 8101)->value('cliente_referencia'));
        $this->assertSame(
            '<p>Conteudo original restrito</p>',
            DB::table('peticoes')->where('id', 8101)->value('conteudo_html')
        );
    }

    public function test_guest_is_sent_to_login_before_any_authorization()
    {
        $this->get('/peticoes-salvas/8101/impressao')->assertRedirect('/login');
    }

    public function test_saved_pieces_list_follows_the_same_rule()
    {
        foreach ([$this->owner, $this->manager, $this->admin] as $user) {
            $this->actingAs($user)->get('/pecas')->assertStatus(200)->assertSee('Cliente Restrito');
        }

        foreach ([$this->colleague, $this->otherManager] as $user) {
            $this->actingAs($user)->get('/pecas')->assertStatus(200)->assertDontSee('Cliente Restrito');
        }
    }

    public function test_dashboard_lists_only_peticoes_the_user_can_open()
    {
        $this->actingAs($this->manager)->get('/painel')->assertStatus(200)->assertSee('Cliente Restrito');
        $this->actingAs($this->colleague)->get('/painel')->assertStatus(200)->assertDontSee('Cliente Restrito');
    }
}
