<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedModeloCreateTest extends TestCase
{
    public function test_admin_can_create_model_first_in_normalized_schema_without_legacy_reflection_by_default()
    {
        $admin = factory(User::class)->create([
            'nivel_usu' => 'ADM',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            'id_setor' => 10,
            'nome_setor' => 'Recuperacao',
            'cod_setor' => 'REC',
            'data_cad' => now(),
        ]);

        DB::table('tp_config_db')->insert([
            'id_db' => 54,
            'nome_db' => 'NEO',
            'ip_db' => '127.0.0.1',
            'data_db' => 'neo',
            'usu_db' => 'sa',
            'senha_db' => '123',
            'table_db' => 'Processos',
            'chave_db' => 'CodigoProcesso',
            'query_db' => 'select * from Processos',
            'where_db' => 'where CodigoProcesso = ?',
            'stt' => 'Y',
        ]);

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados/create')
            ->assertStatus(200)
            ->assertSee('Novo modelo')
            ->assertSee('NEO');

        $response = $this->actingAs($admin)
            ->post('/admin/modelos-normalizados', [
                'tipo_nome' => 'Modelo Criado Normalizado',
                'nome_pre' => 'Descricao Criada',
                'nome_pos' => 'Pos Criado',
                'id_db' => '',
                'id_cliente' => '',
                'id_setor' => 10,
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
                'cod_cabec' => '<p>Cabecalho Criado</p>',
                'cod_rodap' => '<p>Rodape Criado</p>',
            ]);

        $modelo = DB::table('peticao_modelos')->where('nome', 'Modelo Criado Normalizado')->first();
        $this->assertNotNull($modelo);
        $this->assertNull($modelo->legacy_tipo_id);
        $this->assertSame('Modelo Criado Normalizado', $modelo->nome);
        $this->assertSame('ativo', $modelo->status);
        $this->assertSame('pdf', $modelo->arquivo_padrao);
        $this->assertSame('<p>Cabecalho Criado</p>', $modelo->cabecalho_html);
        $this->assertSame('<p>Rodape Criado</p>', $modelo->rodape_html);

        $metadata = json_decode($modelo->metadata, true);
        $this->assertSame('Descricao Criada', $metadata['nome_pre']);
        $this->assertSame('Pos Criado', $metadata['nome_pos']);

        $this->assertNull(DB::table('tp_tipo_tb')->where('tipo_nome', 'Modelo Criado Normalizado')->first());

        $response->assertRedirect('/admin/modelos-normalizados/' . $modelo->id . '/edit');
    }
}
