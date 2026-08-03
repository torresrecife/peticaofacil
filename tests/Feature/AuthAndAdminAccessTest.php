<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AuthAndAdminAccessTest extends TestCase
{
    public function test_guest_is_redirected_to_login()
    {
        $this->get('/painel')
            ->assertRedirect('/login');
    }

    public function test_active_user_can_log_in_and_reach_dashboard()
    {
        factory(User::class)->create([
            'login_usu' => 'admin',
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
    }

    public function test_first_access_user_is_forced_to_change_password()
    {
        factory(User::class)->create([
            'login_usu' => 'primeiro',
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

        $this->assertSame(md5('nova1234'), User::where('login_usu', 'primeiro')->first()->senha_usu);
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
