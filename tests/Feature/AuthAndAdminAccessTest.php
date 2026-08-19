<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthAndAdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            $this->setUpLegacySchema();
        }
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/')
            ->assertRedirect('/login');

        $this->get('/painel')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_from_home_to_dashboard()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/painel');
    }

    public function test_active_user_can_log_in_and_reach_dashboard()
    {
        $user = factory(User::class)->create([
            'login_usu' => 'admin',
            'password' => md5('secret'),
            'senha_usu' => md5('secret'),
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'secret',
        ])->assertRedirect('/painel');

        $this->get('/painel')
            ->assertStatus(200)
            ->assertSee('Peticao Facil');

        $user->refresh();
        $this->assertTrue(Hash::check('secret', $user->password));
        $this->assertTrue(Hash::check('secret', $user->senha_usu));
        $this->assertNotSame(md5('secret'), $user->password);
    }

    public function test_user_with_bcrypt_password_can_log_in_without_reverting_hash()
    {
        $hash = Hash::make('modern-secret');
        $user = factory(User::class)->create([
            'login_usu' => 'modern-user',
            'password' => $hash,
            'senha_usu' => $hash,
            'acesso_usu' => now(),
        ]);

        $this->post('/login', [
            'username' => 'modern-user',
            'password' => 'modern-secret',
        ])->assertRedirect('/painel');

        $user->refresh();
        $this->assertTrue(Hash::check('modern-secret', $user->password));
        $this->assertTrue(Hash::check('modern-secret', $user->senha_usu));
    }

    public function test_invalid_legacy_password_is_rejected_without_upgrading_hash()
    {
        $user = factory(User::class)->create([
            'login_usu' => 'legacy-invalid',
            'password' => md5('correct-secret'),
            'senha_usu' => md5('correct-secret'),
            'acesso_usu' => now(),
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'legacy-invalid',
            'password' => 'wrong-secret',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertSame(md5('correct-secret'), $user->password);
        $this->assertSame(md5('correct-secret'), $user->senha_usu);
        $this->assertGuest();
    }

    public function test_first_access_user_is_forced_to_change_password()
    {
        factory(User::class)->create([
            'login_usu' => 'primeiro',
            'password' => md5('secret'),
            'senha_usu' => md5('secret'),
            'nivel_usu' => 'USU',
            'acesso_usu' => null,
        ]);

        $this->post('/login', [
            'username' => 'primeiro',
            'password' => 'secret',
        ])->assertRedirect('/primeiro-acesso');

        $this->get('/primeiro-acesso')
            ->assertStatus(200)
            ->assertSee('Troca obrigatoria de senha');

        $this->post('/primeiro-acesso', [
            'password' => 'nova1234',
            'password_confirmation' => 'nova1234',
        ])->assertRedirect('/painel');

        $user = User::where('login_usu', 'primeiro')->first();
        $this->assertTrue(Hash::check('nova1234', $user->password));
        $this->assertTrue(Hash::check('nova1234', $user->senha_usu));
    }

    public function test_non_admin_user_cannot_access_admin_area()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
        ]);

        $this->actingAs($user)
            ->get('/admin/usuarios')
            ->assertStatus(403);
    }

    public function test_non_admin_user_does_not_see_admin_menu_links()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
        ]);

        $this->actingAs($user)
            ->get('/painel')
            ->assertStatus(200)
            ->assertDontSee('href="' . route('admin.usuarios.index') . '"', false)
            ->assertDontSee('href="' . route('admin.setores.index') . '"', false)
            ->assertDontSee('href="' . route('admin.clientes.index') . '"', false)
            ->assertDontSee('href="' . route('admin.servidores-normalizados.index') . '"', false)
            ->assertDontSee('href="' . route('admin.modelos-normalizados.index') . '"', false)
            ->assertDontSee('href="' . route('admin.listas.index') . '"', false)
            ->assertDontSee('href="' . route('status') . '"', false);
    }

    public function test_admin_user_can_access_admin_area()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'ADM',
        ]);

        $this->actingAs($user)
            ->get('/admin/usuarios')
            ->assertStatus(200)
            ->assertSee('Usuarios');
    }

    public function test_admin_created_user_password_is_stored_with_bcrypt()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'nome_usu' => 'Novo Usuario Seguro',
            'login_usu' => 'novo-seguro',
            'email_usu' => 'novo-seguro@example.test',
            'nivel_usu' => 'USU',
            'status_usu' => 'ATI',
            'id_setor' => '',
            'cliente_ids' => [],
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ])->assertRedirect('/admin/usuarios');

        $user = User::where('login_usu', 'novo-seguro')->firstOrFail();
        $this->assertTrue(Hash::check('senha-segura', $user->password));
        $this->assertTrue(Hash::check('senha-segura', $user->senha_usu));
    }

    public function test_admin_user_sees_admin_menu_links()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'ADM',
        ]);

        $this->actingAs($user)
            ->get('/painel')
            ->assertStatus(200)
            ->assertSee('href="' . route('admin.usuarios.index') . '"', false)
            ->assertSee('href="' . route('admin.setores.index') . '"', false)
            ->assertSee('href="' . route('admin.clientes.index') . '"', false)
            ->assertSee('href="' . route('admin.servidores-normalizados.index') . '"', false)
            ->assertSee('href="' . route('admin.modelos-normalizados.index') . '"', false)
            ->assertSee('href="' . route('admin.listas.index') . '"', false)
            ->assertSee('href="' . route('status') . '"', false);
    }
}
