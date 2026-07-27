<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminLegacyTipoFallbackTest extends TestCase
{
    public function test_legacy_tipo_routes_require_explicit_sync_before_normalized_editing()
    {
        config()->set('legacy.compat_admin_model_routes', true);

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 21, 'nome_setor' => 'Consumo', 'cod_setor' => 'CON', 'data_cad' => now()],
            ['id_setor' => 22, 'nome_setor' => 'Revisional', 'cod_setor' => 'REV', 'data_cad' => now()],
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 210,
            'tipo_nome' => 'Modelo So Legado',
            'nome_pre' => 'Descricao Legada',
            'id_setor' => 21,
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
            'cod_cabec' => '<p>Cabecalho Legado</p>',
            'cod_rodap' => '<p>Rodape Legado</p>',
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos/210/edit')
            ->assertRedirect('/admin/modelos-normalizados');

        $this->actingAs($admin)
            ->post('/admin/modelos/210/sincronizar')
            ->assertRedirect('/admin/modelos-normalizados/1/edit');

        $this->actingAs($admin)
            ->put('/admin/modelos/210', [
                'tipo_nome' => 'Modelo Legado Atualizado',
                'nome_pre' => 'Descricao Atualizada',
                'nome_pos' => 'Pos Legado',
                'id_db' => '',
                'id_cliente' => '',
                'id_setor' => 22,
                'tipo_stt' => 'N',
                'tipo_arq' => 'word',
                'cod_cabec' => '<p>Cabecalho Novo</p>',
                'cod_rodap' => '<p>Rodape Novo</p>',
            ])
            ->assertRedirect('/admin/modelos-normalizados/1/edit');

        $tipo = DB::table('tp_tipo_tb')->where('tipo_id', 210)->first();
        $mirror = DB::table('peticao_modelos')->where('legacy_tipo_id', 210)->first();

        $this->assertNotNull($mirror);
        $this->assertSame('Modelo Legado Atualizado', $mirror->nome);
        $this->assertSame('word', $mirror->arquivo_padrao);
        $this->assertSame(22, (int) $mirror->legacy_setor_id);

        $metadata = json_decode($mirror->metadata, true);
        $this->assertSame('Descricao Atualizada', $metadata['nome_pre']);
        $this->assertSame('Pos Legado', $metadata['nome_pos']);

        $this->assertSame('Modelo So Legado', $tipo->tipo_nome);
        $this->assertSame('Descricao Legada', $tipo->nome_pre);
        $this->assertNull($tipo->nome_pos);
        $this->assertSame('pdf', $tipo->tipo_arq);
        $this->assertSame('Y', $tipo->tipo_stt);
        $this->assertSame(21, (int) $tipo->id_setor);
    }

    public function test_legacy_tipo_route_returns_gone_when_admin_model_compat_is_disabled()
    {
        config()->set('legacy.compat_admin_model_routes', false);

        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos/210/edit')
            ->assertStatus(410);
    }
}
