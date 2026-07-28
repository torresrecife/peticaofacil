<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminLegacyMirrorDelegationTest extends TestCase
{
    public function test_legacy_admin_model_write_routes_are_not_available_anymore()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)->put('/admin/modelos/111', [])->assertStatus(404);
        $this->actingAs($admin)->post('/admin/modelos/111/paragrafos', [])->assertStatus(404);
        $this->actingAs($admin)->post('/admin/modelos/111/campos', [])->assertStatus(404);
    }
}
