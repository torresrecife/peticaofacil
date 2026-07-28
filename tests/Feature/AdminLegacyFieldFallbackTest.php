<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminLegacyFieldFallbackTest extends TestCase
{
    public function test_legacy_admin_model_field_and_paragraph_routes_are_not_available_anymore()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)->post('/admin/modelos/310/paragrafos', [])->assertStatus(404);
        $this->actingAs($admin)->post('/admin/modelos/310/campos', [])->assertStatus(404);
    }
}
