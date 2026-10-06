<?php

namespace Tests\Feature;

use App\SqlServerProfile;
use App\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlServerProfilePasswordTest extends TestCase
{
    private function admin()
    {
        return factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);
    }

    private function payload(array $overrides = [])
    {
        return array_merge([
            'nome_db' => 'Servidor Protegido',
            'ip_db' => '10.0.0.15',
            'data_db' => 'juridico',
            'usu_db' => 'consulta',
            'senha_db' => 'segredo-do-neo',
            'table_db' => 'Processos',
            'chave_db' => 'CodigoProcesso',
            'query_db' => 'SELECT * FROM Processos',
            'where_db' => 'where 1=1',
            'stt' => 'Y',
        ], $overrides);
    }

    private function rawPassword($id)
    {
        return DB::table('sql_server_profiles')->where('id', $id)->value('password');
    }

    public function test_password_is_stored_encrypted_and_read_back_in_clear()
    {
        $this->actingAs($this->admin())->post('/admin/servidores-normalizados', $this->payload());

        $profile = SqlServerProfile::where('nome', 'Servidor Protegido')->firstOrFail();
        $raw = $this->rawPassword($profile->id);

        $this->assertNotSame('segredo-do-neo', $raw);
        $this->assertStringNotContainsString('segredo-do-neo', (string) $raw);
        $this->assertSame('segredo-do-neo', Crypt::decryptString($raw));
        $this->assertSame('segredo-do-neo', $profile->senha_db);
        $this->assertTrue($profile->hasPassword());
        $this->assertArrayNotHasKey('password', $profile->toArray());
    }

    public function test_edit_screen_never_sends_the_password_back()
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/servidores-normalizados', $this->payload());
        $profile = SqlServerProfile::where('nome', 'Servidor Protegido')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/servidores-normalizados/' . $profile->id . '/edit')
            ->assertStatus(200)
            ->assertSee('type="password" name="senha_db" value=""', false)
            ->assertSee('Senha cadastrada')
            ->assertDontSee('segredo-do-neo')
            ->assertDontSee($this->rawPassword($profile->id));
    }

    public function test_blank_password_on_update_keeps_the_current_one_and_a_new_value_replaces_it()
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/servidores-normalizados', $this->payload());
        $profile = SqlServerProfile::where('nome', 'Servidor Protegido')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/servidores-normalizados/' . $profile->id, $this->payload(['senha_db' => '', 'usu_db' => 'outro']))
            ->assertRedirect('/admin/servidores-normalizados/' . $profile->id . '/edit');

        $profile->refresh();
        $this->assertSame('outro', $profile->usu_db);
        $this->assertSame('segredo-do-neo', $profile->senha_db);

        $this->actingAs($admin)
            ->put('/admin/servidores-normalizados/' . $profile->id, $this->payload(['senha_db' => 'nova-senha']))
            ->assertRedirect('/admin/servidores-normalizados/' . $profile->id . '/edit');

        $profile->refresh();
        $this->assertSame('nova-senha', $profile->senha_db);
        $this->assertSame('nova-senha', Crypt::decryptString($this->rawPassword($profile->id)));
    }

    public function test_plain_text_password_from_before_the_migration_is_still_readable()
    {
        DB::table('sql_server_profiles')->insert([
            'id' => 910,
            'nome' => 'Legado',
            'host' => '10.0.0.10',
            'database_name' => 'juridico',
            'username' => 'sa',
            'password' => 'texto-puro',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('texto-puro', SqlServerProfile::findOrFail(910)->senha_db);
    }

    public function test_migration_encrypts_existing_passwords_and_can_run_twice()
    {
        require_once database_path('migrations/2026_10_05_000100_encrypt_sql_server_profile_passwords.php');

        $base = [
            'host' => '10.0.0.10',
            'database_name' => 'juridico',
            'username' => 'sa',
            'status' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('sql_server_profiles')->insert($base + ['id' => 921, 'nome' => 'Texto puro', 'password' => 'senha-antiga']);
        DB::table('sql_server_profiles')->insert($base + ['id' => 922, 'nome' => 'Ja cifrada', 'password' => Crypt::encryptString('ja-protegida')]);
        DB::table('sql_server_profiles')->insert($base + ['id' => 923, 'nome' => 'Sem senha', 'password' => null]);

        $migration = new \EncryptSqlServerProfilePasswords();
        $migration->up();

        $firstRun = $this->rawPassword(921);
        $this->assertNotSame('senha-antiga', $firstRun);
        $this->assertSame('senha-antiga', Crypt::decryptString($firstRun));
        $this->assertSame('ja-protegida', Crypt::decryptString($this->rawPassword(922)));
        $this->assertNull($this->rawPassword(923));

        $migration->up();
        $this->assertSame($firstRun, $this->rawPassword(921));
        $this->assertSame('senha-antiga', SqlServerProfile::findOrFail(921)->senha_db);

        $migration->down();
        $this->assertSame('senha-antiga', $this->rawPassword(921));
        $this->assertSame('ja-protegida', $this->rawPassword(922));
    }
}
