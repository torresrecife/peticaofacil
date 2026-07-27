<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class AdminModeloIndexRedirectTest extends TestCase
{
    public function test_legacy_model_index_redirects_to_normalized_index()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos')
            ->assertRedirect('/admin/modelos-normalizados');
    }
}
