<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTodaySummaryTest extends TestCase
{
    public function test_dashboard_shows_today_petitions_and_user_totals()
    {
        $admin = factory(User::class)->create([
            'id_usu' => 900,
            'nome_usu' => 'Administrador',
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_usu_tb')->insert([
            [
                'id_usu' => 901,
                'nome_usu' => 'Fabio',
                'login_usu' => 'fabio',
                'senha_usu' => md5('123'),
                'nivel_usu' => 'USU',
                'status_usu' => 'ATI',
                'data_cad' => now(),
                'acesso_usu' => now(),
            ],
            [
                'id_usu' => 902,
                'nome_usu' => 'Maria',
                'login_usu' => 'maria',
                'senha_usu' => md5('123'),
                'nivel_usu' => 'USU',
                'status_usu' => 'ATI',
                'data_cad' => now(),
                'acesso_usu' => now(),
            ],
        ]);

        DB::table('users')->insert([
            [
                'id' => 901,
                'legacy_usuario_id' => 901,
                'name' => 'Fabio',
                'nome_usu' => 'Fabio',
                'login_usu' => 'fabio',
                'senha_usu' => md5('123'),
                'password' => md5('123'),
                'nivel_usu' => 'USU',
                'status_usu' => 'ATI',
                'acesso_usu' => now(),
                'data_cad' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 902,
                'legacy_usuario_id' => 902,
                'name' => 'Maria',
                'nome_usu' => 'Maria',
                'login_usu' => 'maria',
                'senha_usu' => md5('123'),
                'password' => md5('123'),
                'nivel_usu' => 'USU',
                'status_usu' => 'ATI',
                'acesso_usu' => now(),
                'data_cad' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('peticao_modelos')->insert([
            [
                'id' => 901,
                'legacy_tipo_id' => null,
                'nome' => 'Modelo Civel',
                'slug' => 'modelo-civel-901',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 902,
                'legacy_tipo_id' => null,
                'nome' => 'Modelo Trabalhista',
                'slug' => 'modelo-trabalhista-902',
                'status' => 'ativo',
                'arquivo_padrao' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('peticoes')->insert([
            [
                'id' => 1,
                'legacy_peca_id' => null,
                'modelo_id' => 901,
                'user_id' => 901,
                'legacy_usuario_id' => 901,
                'codigo_externo' => null,
                'nome_arquivo' => 'Modelo Civel',
                'cliente_referencia' => 'Cliente Alfa',
                'conteudo_html' => '<p>A</p>',
                'campos_resolvidos' => null,
                'gerado_em' => now()->subHour(),
                'salvo_em' => now()->subHour(),
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
            [
                'id' => 2,
                'legacy_peca_id' => null,
                'modelo_id' => 902,
                'user_id' => 901,
                'legacy_usuario_id' => 901,
                'codigo_externo' => null,
                'nome_arquivo' => 'Modelo Trabalhista',
                'cliente_referencia' => 'Cliente Beta',
                'conteudo_html' => '<p>B</p>',
                'campos_resolvidos' => null,
                'gerado_em' => now()->subMinutes(30),
                'salvo_em' => now()->subMinutes(30),
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'id' => 3,
                'legacy_peca_id' => null,
                'modelo_id' => 902,
                'user_id' => 902,
                'legacy_usuario_id' => 902,
                'codigo_externo' => null,
                'nome_arquivo' => 'Modelo Trabalhista',
                'cliente_referencia' => 'Cliente Gama',
                'conteudo_html' => '<p>C</p>',
                'campos_resolvidos' => null,
                'gerado_em' => now()->subMinutes(10),
                'salvo_em' => now()->subMinutes(10),
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ],
            [
                'id' => 4,
                'legacy_peca_id' => null,
                'modelo_id' => 901,
                'user_id' => 902,
                'legacy_usuario_id' => 902,
                'codigo_externo' => null,
                'nome_arquivo' => 'Modelo Civel',
                'cliente_referencia' => 'Cliente Ontem',
                'conteudo_html' => '<p>D</p>',
                'campos_resolvidos' => null,
                'gerado_em' => now()->subDay(),
                'salvo_em' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'id' => 5,
                'legacy_peca_id' => 1005,
                'modelo_id' => 901,
                'user_id' => 901,
                'legacy_usuario_id' => 901,
                'codigo_externo' => null,
                'nome_arquivo' => 'Modelo Civel',
                'cliente_referencia' => 'Cliente Sincronizado Hoje',
                'conteudo_html' => '<p>E</p>',
                'campos_resolvidos' => null,
                'gerado_em' => now()->subYears(4),
                'salvo_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->get('/painel')
            ->assertStatus(200)
            ->assertSee('Peticoes de hoje')
            ->assertSee('Usuarios do dia')
            ->assertSee('Cliente Alfa')
            ->assertSee('Cliente Beta')
            ->assertSee('Cliente Gama')
            ->assertDontSee('Cliente Ontem')
            ->assertDontSee('Cliente Sincronizado Hoje')
            ->assertSee('Fabio')
            ->assertSee('Maria')
            ->assertSee('Modelo Civel')
            ->assertSee('Modelo Trabalhista')
            ->assertSee('2')
            ->assertSee('Normalizada');
    }
}
