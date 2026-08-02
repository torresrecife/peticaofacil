<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeticaoAvulsaCreationTest extends TestCase
{
    public function test_user_can_create_avulsa_peticao_and_open_editor()
    {
        $user = factory(User::class)->create([
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        $this->actingAs($user)
            ->get('/peticoes-avulsas/criar')
            ->assertStatus(200)
            ->assertSee('Criar peticao avulsa')
            ->assertSee('Tipo de peticao')
            ->assertSee('Nome da parte contraria')
            ->assertSee('Codigo do processo (opcional)');

        DB::table('peticao_modelos')->insert([
            'id' => 999,
            'nome' => 'Peticao avulsa',
            'slug' => '__peticao-avulsa__',
            'status' => 'ativo',
            'arquivo_padrao' => 'doc',
            'cabecalho_html' => '<p><strong>@TIPO_PETICAO@</strong></p><p>@CODIGO_PROCESSO@</p>',
            'rodape_html' => '<p>@PARTE_CONTRARIA@</p><p>@DATA_ATUAL@</p>',
            'metadata' => json_encode(['system' => 'avulsa']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post('/peticoes-avulsas', [
                'tipo_peticao' => 'Manifestacao avulsa',
                'parte_contraria' => 'Empresa XPTO Ltda',
                'codigo_processo' => '0030623-76.2021.8.17.2810',
            ]);

        $peticao = DB::table('peticoes')
            ->where('nome_arquivo', 'Manifestacao avulsa')
            ->where('cliente_referencia', 'Empresa XPTO Ltda')
            ->first();

        $this->assertNotNull($peticao);
        $this->assertNotNull($peticao->modelo_id);
        $this->assertStringContainsString('Manifestacao avulsa', $peticao->conteudo_html);
        $this->assertStringContainsString('0030623-76.2021.8.17.2810', $peticao->conteudo_html);
        $this->assertStringContainsString('Empresa XPTO Ltda', $peticao->conteudo_html);
        $this->assertSame('0030623-76.2021.8.17.2810', $peticao->codigo_externo);

        $modeloAvulso = DB::table('peticao_modelos')->where('id', $peticao->modelo_id)->first();
        $this->assertSame('__peticao-avulsa__', $modeloAvulso->slug);

        $response->assertRedirect('/peticoes-salvas/' . $peticao->id . '/editar');

        $this->actingAs($user)
            ->get('/peticoes-salvas/' . $peticao->id . '/editar')
            ->assertStatus(200)
            ->assertSee('Manifestacao avulsa')
            ->assertSee('Empresa XPTO Ltda');

        $version = DB::table('peticao_versoes')->where('peticao_id', $peticao->id)->first();
        $this->assertNotNull($version);
        $this->assertSame('draft', $version->origem_snapshot);
    }
}
