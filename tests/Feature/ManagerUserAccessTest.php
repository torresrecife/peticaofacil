<?php

namespace Tests\Feature;

use App\Cliente;
use App\User;
use App\Setor;
use Tests\TestCase;

class ManagerUserAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Setor::create(['nome_setor' => 'Setor A']);
        Setor::create(['nome_setor' => 'Setor B']);
    }

    private function account($role, $clients, $sector = 1)
    {
        return factory(User::class)->create(['nivel_usu' => $role, 'id_cliente' => $clients, 'id_setor' => $sector]);
    }

    private function data($login = 'managed_user')
    {
        return [
            'nome_usu' => 'Usuario gerenciado', 'login_usu' => $login,
            'nivel_usu' => 'USU', 'status_usu' => 'ATI', 'id_setor' => 1,
            'cliente_ids' => [1], 'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];
    }

    public function test_manager_listing_and_direct_requests_are_limited_to_entire_portfolio()
    {
        $manager = $this->account('GER', '1,2');
        $allowed = $this->account('USU', '1,2');
        $blocked = [
            $this->account('ADM', '1'), $this->account('GER', '1'), $manager,
            $this->account('USU', '1,3'), $this->account('USU', '3'),
            $this->account('USU', '0'), $this->account('USU', '11'),
            $this->account('USU', '1', 2), $this->account('USU', '1', null),
        ];
        $response = $this->actingAs($manager)->get('/admin/usuarios')->assertOk();
        $this->assertSame([$allowed->id], $response->viewData('users')->pluck('id')->all());
        foreach ($blocked as $target) {
            $this->get('/admin/usuarios/' . $target->id . '/edit')->assertStatus(403);
            $this->put('/admin/usuarios/' . $target->id, $this->data())->assertStatus(403);
        }
        $this->get('/admin/usuarios/' . $allowed->id . '/edit')->assertOk();
        $this->assertSame([1], $this->get('/admin/usuarios/create')->viewData('setores')->pluck('id_setor')->all());
    }

    public function test_manager_can_create_and_update_but_cannot_promote_or_expand_access()
    {
        Cliente::create(['cliente_name' => 'Carteira A']);
        Cliente::create(['cliente_name' => 'Carteira B']);
        $manager = $this->account('GER', '1');
        $this->actingAs($manager)->post('/admin/usuarios', $this->data())->assertOk();
        $target = User::where('login_usu', 'managed_user')->firstOrFail();
        $this->assertSame('1', $target->id_cliente);
        foreach ([['id_setor' => 2], ['id_setor' => null], ['id_setor' => 0], ['nivel_usu' => 'ADM'], ['nivel_usu' => 'GER'], ['cliente_ids' => [2]], ['cliente_ids' => []], ['cliente_ids' => [0]]] as $invalid) {
            $data = array_replace($this->data(), $invalid);
            $this->put('/admin/usuarios/' . $target->id, $data)->assertSessionHasErrors();
            $data['login_usu'] = 'forbidden_new';
            $this->post('/admin/usuarios', $data)->assertSessionHasErrors();
            $this->assertFalse(User::where('login_usu', 'forbidden_new')->exists());
        }
        $data = $this->data();
        $data['status_usu'] = 'INA';
        $this->put('/admin/usuarios/' . $target->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('INA', $target->fresh()->status_usu);
        $this->assertSame('USU', $target->fresh()->nivel_usu);
        $this->assertSame('1', $target->fresh()->id_cliente);
        $this->assertSame(1, (int) $target->fresh()->id_setor);
    }

    public function test_manager_without_explicit_clients_cannot_create_and_admin_keeps_global_access()
    {
        $manager = $this->account('GER', '0');
        $this->actingAs($manager)->get('/admin/usuarios/create')->assertStatus(403);
        $this->post('/admin/usuarios', $this->data())->assertStatus(403);
        $this->assertCount(0, $this->get('/admin/usuarios')->viewData('users'));
        $admin = $this->account('ADM', '0');
        $this->app['session']->forget('password_hash');
        $this->actingAs($admin)->get('/admin/usuarios/' . $manager->id . '/edit')->assertOk();
        $data = $this->data('new_manager');
        $data['nivel_usu'] = 'GER';
        $this->post('/admin/usuarios', $data)->assertSessionHasNoErrors()->assertOk();
        $this->assertSame('GER', User::where('login_usu', 'new_manager')->firstOrFail()->nivel_usu);
    }
}
