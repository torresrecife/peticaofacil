<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_users_by_name_login_email_and_id()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'status_usu' => 'ATI',
        ]);

        $target = factory(User::class)->create([
            'nome_usu' => 'Carlos Andrade',
            'login_usu' => 'c.andrade',
            'email_usu' => 'carlos.andrade@example.com',
        ]);

        $other = factory(User::class)->create([
            'nome_usu' => 'Marina Torres',
            'login_usu' => 'm.torres',
            'email_usu' => 'marina.torres@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/usuarios?q=Carlos')
            ->assertOk()
            ->assertSee('Carlos Andrade')
            ->assertDontSee('Marina Torres');

        $this->actingAs($admin)
            ->get('/admin/usuarios?q=c.andrade')
            ->assertOk()
            ->assertSee('Carlos Andrade')
            ->assertDontSee('Marina Torres');

        $this->actingAs($admin)
            ->get('/admin/usuarios?q=carlos.andrade@example.com')
            ->assertOk()
            ->assertSee('Carlos Andrade')
            ->assertDontSee('Marina Torres');

        $this->actingAs($admin)
            ->get('/admin/usuarios?q=' . $target->id)
            ->assertOk()
            ->assertSee('Carlos Andrade')
            ->assertDontSee('Marina Torres');

        $this->actingAs($admin)
            ->get('/admin/usuarios?q=nao-existe')
            ->assertOk()
            ->assertSee('Nenhum usuario encontrado.');
    }
}
