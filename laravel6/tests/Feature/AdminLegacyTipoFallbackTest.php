<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminLegacyTipoFallbackTest extends TestCase
{
    public function test_legacy_tipo_fallback_edit_and_update_still_work_without_mirror()
    {
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

        $this->assertSame('Modelo Legado Atualizado', $tipo->tipo_nome);
        $this->assertSame('Descricao Atualizada', $tipo->nome_pre);
        $this->assertSame('Pos Legado', $tipo->nome_pos);
        $this->assertSame('word', $tipo->tipo_arq);
        $this->assertSame('N', $tipo->tipo_stt);
        $this->assertSame(22, (int) $tipo->id_setor);

        $this->assertNotNull($mirror);
        $this->assertSame('Modelo Legado Atualizado', $mirror->nome);
    }
}
