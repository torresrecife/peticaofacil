<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNormalizedModeloCreateTest extends TestCase
{
    public function test_admin_can_create_model_first_in_normalized_schema_and_reflect_to_legacy()
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

        $this->actingAs($admin)
            ->get('/admin/modelos-normalizados/create')
            ->assertStatus(200)
            ->assertSee('Novo modelo');

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
        $this->assertNotNull($modelo->legacy_tipo_id);

        $tipo = DB::table('tp_tipo_tb')->where('tipo_id', $modelo->legacy_tipo_id)->first();
        $this->assertNotNull($tipo);
        $this->assertSame('Modelo Criado Normalizado', $tipo->tipo_nome);
        $this->assertSame('Descricao Criada', $tipo->nome_pre);
        $this->assertSame('Pos Criado', $tipo->nome_pos);

        $response->assertRedirect('/admin/modelos-normalizados/' . $modelo->id . '/edit');
    }
}
