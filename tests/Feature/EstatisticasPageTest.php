<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireInitialPasswordChange;
use App\User;
use Tests\TestCase;

class EstatisticasPageTest extends TestCase
{
    public function test_authenticated_user_can_view_statistics_page_with_graph_sections()
    {
        $this->withoutMiddleware(RequireInitialPasswordChange::class);

        $user = factory(User::class)->make([
            'id' => 920,
            'legacy_usuario_id' => 920,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($user)
            ->get('/estatisticas')
            ->assertStatus(200)
            ->assertSee('Estatisticas do sistema')
            ->assertSee('Producao dos ultimos 14 dias')
            ->assertSee('Origem das peticoes')
            ->assertSee('Modelos mais usados')
            ->assertSee('Usuarios com mais producao')
            ->assertSee('Avulsa')
            ->assertSee('Modelo');
    }

    public function test_statistics_menu_item_is_visible_for_regular_user()
    {
        $this->withoutMiddleware(RequireInitialPasswordChange::class);

        $user = factory(User::class)->make([
            'nivel_usu' => 'USU',
        ]);

        $this->actingAs($user)
            ->get('/painel')
            ->assertStatus(200)
            ->assertSee('href="' . route('estatisticas') . '"', false);
    }
}
