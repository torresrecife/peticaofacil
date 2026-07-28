<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminLegacySqlServerFallbackTest extends TestCase
{
    public function test_legacy_admin_sql_routes_are_not_available_anymore()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/servidores/77/edit')->assertStatus(404);
        $this->actingAs($admin)->put('/admin/servidores/77', [])->assertStatus(404);
    }
}
