<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModeloFavoritoTest extends TestCase
{
    public function test_user_can_toggle_normalized_and_legacy_favorites()
    {
        $user = factory(User::class)->create([
            'id_usu' => 500,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_tipo_tb')->insert([
            'tipo_id' => 801,
            'tipo_nome' => 'Modelo So Legado',
            'tipo_data' => now(),
            'tipo_stt' => 'Y',
            'tipo_arq' => 'pdf',
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 701,
            'legacy_tipo_id' => 701,
            'nome' => 'Modelo Favorito Normalizado',
            'slug' => 'modelo-favorito-normalizado-701',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/peticoes/modelos/701/favorito')
            ->assertRedirect();

        $this->actingAs($user)
            ->post('/peticoes/801/favorito')
            ->assertRedirect();

        $this->assertDatabaseHas('user_model_favorites', [
            'legacy_usuario_id' => 500,
            'source' => 'normalized',
            'modelo_id' => 701,
        ]);

        $this->assertDatabaseHas('user_model_favorites', [
            'legacy_usuario_id' => 500,
            'source' => 'legacy',
            'legacy_tipo_id' => 801,
        ]);

        $this->actingAs($user)
            ->delete('/peticoes/modelos/701/favorito')
            ->assertRedirect();

        $this->assertDatabaseMissing('user_model_favorites', [
            'legacy_usuario_id' => 500,
            'source' => 'normalized',
            'modelo_id' => 701,
        ]);
    }

    public function test_dashboard_shows_user_favorites()
    {
        $user = factory(User::class)->create([
            'id_usu' => 500,
            'nivel_usu' => 'USU',
            'acesso_usu' => now(),
        ]);

        DB::table('tp_setor_tb')->insert([
            ['id_setor' => 1, 'nome_setor' => 'Civel', 'cod_setor' => 'CIV', 'data_cad' => now()],
            ['id_setor' => 2, 'nome_setor' => 'Trabalhista', 'cod_setor' => 'TRB', 'data_cad' => now()],
        ]);

        DB::table('tp_tipo_tb')->insert([
            [
                'tipo_id' => 801,
                'tipo_nome' => 'Modelo So Legado',
                'id_setor' => 2,
                'tipo_data' => now(),
                'tipo_stt' => 'Y',
                'tipo_arq' => 'pdf',
            ],
        ]);

        DB::table('peticao_modelos')->insert([
            'id' => 701,
            'legacy_tipo_id' => 701,
            'legacy_setor_id' => 1,
            'nome' => 'Modelo Favorito Normalizado',
            'slug' => 'modelo-favorito-normalizado-701',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_model_favorites')->insert([
            [
                'legacy_usuario_id' => 500,
                'source' => 'normalized',
                'modelo_id' => 701,
                'legacy_tipo_id' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'legacy_usuario_id' => 500,
                'source' => 'legacy',
                'modelo_id' => 0,
                'legacy_tipo_id' => 801,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get('/painel')
            ->assertStatus(200)
            ->assertSee('Favoritos')
            ->assertSee('Modelo Favorito Normalizado')
            ->assertSee('Modelo So Legado');
    }
}
