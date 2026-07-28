<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class LegacyEditorRedirectTest extends TestCase
{
    public function test_legacy_piece_editor_route_is_not_available_anymore()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($user)
            ->get('/pecas/5501/editar')
            ->assertStatus(404);
    }
}
