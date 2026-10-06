<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\SqlServerProfile;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlServerProfileLinkTest extends TestCase
{
    public function test_server_created_on_the_screen_can_be_linked_to_a_model()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);
        $this->insertProfile(['id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO']);

        $this->actingAs($admin)->post('/admin/servidores-normalizados', $this->serverForm('Consulta Trabalhista'));

        $profile = SqlServerProfile::where('nome', 'Consulta Trabalhista')->firstOrFail();
        $this->assertSame(55, (int) $profile->legacy_config_id);

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados/create')
            ->assertStatus(200)
            ->assertSee('<option value="55"', false)
            ->assertSee('Consulta Trabalhista');

        $this->actingAs($admin)
            ->post('/admin/modelos-normalizados', $this->modelForm('Modelo com servidor novo', 55))
            ->assertSessionHasNoErrors();

        $modelo = PeticaoModelo::where('nome', 'Modelo com servidor novo')->firstOrFail();
        $this->assertSame(55, (int) $modelo->legacy_sql_config_id);
        $this->assertSame((int) $profile->id, (int) $modelo->servidor->id);

        $edit = $this->actingAs($admin)->get('/admin/modelos-normalizados/' . $modelo->id . '/edit');
        $edit->assertStatus(200);
        $this->assertSame(1, preg_match('/<option value="55"\s+selected/', $edit->getContent()));

        $this->actingAs($admin)
            ->get('/admin/servidores-normalizados/' . $profile->id . '/edit')
            ->assertStatus(200)
            ->assertSee('Codigo de vinculo com os modelos: 55.');
    }

    public function test_new_server_does_not_inherit_models_that_point_to_a_missing_server()
    {
        $this->insertProfile(['id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO']);
        // Modelo que ficou apontando para um servidor que nao existe mais.
        DB::table('peticao_modelos')->insert([
            'id' => 300, 'legacy_sql_config_id' => 70, 'nome' => 'Orfao', 'slug' => 'orfao', 'status' => 'ativo',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $first = SqlServerProfile::create($this->profileAttributes('Primeiro'));
        $second = SqlServerProfile::create($this->profileAttributes('Segundo'));

        $this->assertSame(71, (int) $first->legacy_config_id);
        $this->assertSame(72, (int) $second->legacy_config_id);
        $this->assertNull(PeticaoModelo::findOrFail(300)->servidor);
    }

    public function test_code_informed_on_creation_is_kept()
    {
        $profile = SqlServerProfile::create($this->profileAttributes('Com codigo') + ['legacy_config_id' => 90]);

        $this->assertSame(90, (int) $profile->fresh()->legacy_config_id);
    }

    public function test_model_cannot_be_linked_to_a_server_that_does_not_exist()
    {
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
        DB::table('setores')->insert(['id_setor' => 10, 'nome_setor' => 'Recuperacao', 'cod_setor' => 'REC', 'data_cad' => now()]);
        $this->insertProfile(['id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO']);

        $this->actingAs($admin)
            ->from('/admin/modelos-normalizados/create')
            ->post('/admin/modelos-normalizados', $this->modelForm('Modelo invalido', 999))
            ->assertSessionHasErrors('id_db');

        $this->assertSame(0, PeticaoModelo::where('nome', 'Modelo invalido')->count());
    }

    public function test_migration_gives_a_code_to_servers_created_before_the_fix()
    {
        require_once database_path('migrations/2026_10_05_000200_assign_link_code_to_sql_server_profiles.php');

        $this->insertProfile(['id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO']);
        $this->insertProfile(['id' => 2, 'legacy_config_id' => null, 'nome' => 'Sem codigo A']);
        $this->insertProfile(['id' => 3, 'legacy_config_id' => null, 'nome' => 'Sem codigo B']);
        DB::table('peticao_modelos')->insert([
            'id' => 300, 'legacy_sql_config_id' => 54, 'nome' => 'Usa o NEO', 'slug' => 'usa-o-neo', 'status' => 'ativo',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = new \AssignLinkCodeToSqlServerProfiles();
        $migration->up();

        $codes = DB::table('sql_server_profiles')->orderBy('id')->pluck('legacy_config_id', 'id')->all();
        $this->assertSame([1 => 54, 2 => 55, 3 => 56], array_map('intval', $codes));

        // Rodar de novo nao muda nada, e o modelo continua no mesmo servidor.
        $migration->up();
        $again = DB::table('sql_server_profiles')->orderBy('id')->pluck('legacy_config_id', 'id')->all();
        $this->assertSame([1 => 54, 2 => 55, 3 => 56], array_map('intval', $again));
        $this->assertSame('NEO', PeticaoModelo::findOrFail(300)->servidor->nome);
    }

    private function profileAttributes($name)
    {
        return [
            'nome' => $name,
            'host' => '10.0.0.15',
            'database_name' => 'juridico',
            'username' => 'consulta',
            'status' => 'ativo',
        ];
    }

    private function insertProfile(array $attributes)
    {
        DB::table('sql_server_profiles')->insert($attributes + [
            'host' => '127.0.0.1',
            'database_name' => 'neo',
            'username' => 'leitura',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function serverForm($name)
    {
        return [
            'nome_db' => $name,
            'ip_db' => '10.0.0.15',
            'data_db' => 'juridico',
            'usu_db' => 'consulta',
            'senha_db' => 'segredo',
            'table_db' => 'Processos',
            'chave_db' => 'CodigoProcesso',
            'query_db' => 'SELECT * FROM Processos',
            'where_db' => 'where 1=1',
            'stt' => 'Y',
        ];
    }

    private function modelForm($name, $serverCode)
    {
        return [
            'tipo_nome' => $name,
            'nome_pre' => 'Descricao',
            'nome_pos' => 'Pos',
            'id_db' => $serverCode,
            'id_cliente' => '',
            'id_setor' => 10,
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
            'cod_cabec' => '<p>Cabecalho</p>',
            'cod_rodap' => '<p>Rodape</p>',
        ];
    }
}
