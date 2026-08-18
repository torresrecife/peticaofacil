<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedModeloUpdateTest extends TestCase
{
    public function test_admin_can_update_normalized_model_directly_in_normalized_schema()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 8, 'nome_setor' => 'Civel', 'cod_setor' => 'CIV', 'data_cad' => now()],
            ['id_setor' => 9, 'nome_setor' => 'Contratos', 'cod_setor' => 'CON', 'data_cad' => now()],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 88,
            'legacy_tipo_id' => 88,
            'legacy_setor_id' => 8,
            'nome' => 'Modelo Original',
            'slug' => 'modelo-original-88',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'cabecalho_html' => '<p>Cabecalho Antigo</p>',
            'rodape_html' => '<p>Rodape Antigo</p>',
            'metadata' => json_encode(['nome_pre' => 'Descricao Original']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados/88/edit')
            ->assertStatus(200)
            ->assertSee('Fonte principal atual da edicao.');

        $this->actingAs($admin)
            ->put('/admin/modelos-normalizados/88', [
                'tipo_nome' => 'Modelo Atualizado',
                'nome_pre' => 'Descricao Atualizada',
                'nome_pos' => 'Posfixo',
                'id_db' => '',
                'id_cliente' => '',
                'id_setor' => 9,
                'tipo_stt' => 'N',
                'tipo_arq' => 'word',
                'cod_cabec' => '<p>Cabecalho Novo</p>',
                'cod_rodap' => '<p>Rodape Novo</p>',
            ])
            ->assertRedirect('/admin/modelos-normalizados/88/edit');

        $modelo = DB::table('peticao_modelos')->where('id', 88)->first();

        $this->assertSame('Modelo Atualizado', $modelo->nome);
        $this->assertSame('inativo', $modelo->status);
        $this->assertSame('word', $modelo->arquivo_padrao);
        $this->assertSame(9, (int) $modelo->legacy_setor_id);
        $this->assertSame('<p>Cabecalho Novo</p>', $modelo->cabecalho_html);
        $this->assertSame('<p>Rodape Novo</p>', $modelo->rodape_html);

        $metadata = json_decode($modelo->metadata, true);
        $this->assertSame('Descricao Atualizada', $metadata['nome_pre']);
        $this->assertSame('Posfixo', $metadata['nome_pos']);
    }
}
