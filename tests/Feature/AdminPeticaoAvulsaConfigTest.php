<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPeticaoAvulsaConfigTest extends TestCase
{
    public function test_admin_can_update_avulsa_header_and_footer_template()
    {
        config(['legacy.app_url' => 'https://legacy.invalid/peticaofacil']);

        $user = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin/peticoes-avulsas/configuracao')
            ->assertStatus(200)
            ->assertSee('Configurar peticao avulsa')
            ->assertSee('@TIPO_PETICAO@')
            ->assertSee(asset('ckeditor/ckeditor.js'), false)
            ->assertSee(asset('ckfinder/ckfinder.js'), false)
            ->assertDontSee('legacy.invalid', false);

        $this->actingAs($user)
            ->put('/admin/peticoes-avulsas/configuracao', [
                'cod_cabec' => '<p><strong>@TIPO_PETICAO@</strong></p>',
                'cod_rodap' => '<p>@PARTE_CONTRARIA@</p>',
            ])
            ->assertRedirect('/admin/peticoes-avulsas/configuracao');

        $modelo = DB::table('peticao_modelos')->where('slug', '__peticao-avulsa__')->first();
        $this->assertNotNull($modelo);
        $this->assertStringContainsString('@TIPO_PETICAO@', $modelo->cabecalho_html);
        $this->assertStringContainsString('@PARTE_CONTRARIA@', $modelo->rodape_html);
    }
}
