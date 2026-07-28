<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminSqlServerMirrorRedirectTest extends TestCase
{
    public function test_legacy_sql_server_edit_route_is_not_available_anymore()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/servidores/88/edit')
            ->assertStatus(404);
    }
}
