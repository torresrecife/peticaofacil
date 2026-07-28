<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminLegacyTipoFallbackTest extends TestCase
{
    public function test_legacy_admin_model_routes_are_not_available_anymore()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/modelos/210/edit')->assertStatus(404);
        $this->actingAs($admin)->post('/admin/modelos/210/sincronizar')->assertStatus(404);
        $this->actingAs($admin)->put('/admin/modelos/210', [])->assertStatus(404);
    }
}
