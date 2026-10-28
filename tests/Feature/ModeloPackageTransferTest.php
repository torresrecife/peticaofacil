<?php

namespace Tests\Feature;

use App\PeticaoModelo;
use App\PeticaoModeloCampo;
use App\Services\PeticaoComposerService;
use App\Services\PeticaoModeloPackageService;
use App\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModeloPackageTransferTest extends TestCase
{
    private $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_export_replaces_every_field_id_by_a_key_reference()
    {
        $this->seedOrigin();

        $package = app(PeticaoModeloPackageService::class)->export(PeticaoModelo::findOrFail(500));

        $this->assertSame(2, $package['version']);
        $this->assertSame(['nome_do_reu', 'nome_do_reu_2', 'valor', 'tipo'], array_column($package['fields'], 'key'));
        $this->assertSame([
            'client' => 'ACME',
            'client_code' => 'ACME-AT',
            'sector_name' => 'RCJ - ATACADO',
            'sector_code' => 'RCJ',
            'server' => 'NEO',
            'lists' => ['CLIENTES'],
        ], $package['dependencies']);

        $json = json_encode($package);
        foreach ([1996, 1997, 1998, 1999] as $id) {
            $this->assertStringNotContainsString((string) $id, $json);
        }
        // Marcador de um campo que nao e deste modelo: nao ha chave para ele, fica como esta.
        $this->assertStringContainsString('@campo9999@', $json);

        $this->assertSame(
            '<p>Reu: {{field:nome_do_reu}} / {{field:nome_do_reu_2}} valor {{field:valor}} tipo {{field:tipo}} @campo9999@</p>',
            $package['paragraphs'][0]['conteudo_html']
        );
        $this->assertSame('<p>Cabecalho {{field:nome_do_reu}}</p>', $package['model']['cabecalho_html']);
        $this->assertSame('Peticao {{field:nome_do_reu_2}}', $package['model']['metadata']['nome_pre']);

        $first = $package['fields'][0];
        $this->assertSame('CLIENTES', $first['lista_grupo']);
        $this->assertSame(
            'fc_ajax_comp("tp_lista_tb","return_1","campo{{field_id:nome_do_reu_2}}","unir","id_lista",this,1); '
            . 'mcampo("campo{{field_id:nome_do_reu}}_|_campo{{field_id:nome_do_reu_2}}"); $("#campo{{field_id:nome_do_reu_2}}").focus();',
            $first['eventos_frontend']['focus']
        );
        $this->assertSame(['{{field_id:valor}}'], $first['eventos_frontend']['conditional']['target_ids']);
        $this->assertSame('SIM', $first['eventos_frontend']['conditional']['value']);

        $this->assertSame('Ver {{field:nome_do_reu}}', $package['fields'][1]['valor_padrao']);
        $this->assertSame(
            'repeteValor({{field_id:valor}}, {{field_id:nome_do_reu_2}}, this); fc_newstring(this);',
            $package['fields'][2]['eventos_frontend']['blur']
        );
        $this->assertSame(
            '$(".dis_campo{{field_id:valor}}").show(); fc_numextenso("{{field_id:valor}}","{{field_id:nome_do_reu_2}}","parcela"); '
            . 'mcampo("campo{{field_id:tipo}}_|_campo658");',
            $package['fields'][3]['eventos_frontend']['focus']
        );
        $this->assertCount(2, $package['fields'][3]['options']);
        $this->assertSame('Texto {{field:valor}}', $package['fields'][3]['options'][0]['valor_retorno']);

        foreach ($package['fields'] as $field) {
            foreach (['id', 'modelo_id', 'legacy_input_id', 'token'] as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $field);
            }
        }
    }

    public function test_exported_package_is_rebuilt_with_the_ids_of_the_destination()
    {
        $this->seedOrigin();
        $path = $this->writePackage(app(PeticaoModeloPackageService::class)->export(PeticaoModelo::findOrFail(500)));

        $this->turnIntoDestination();

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $path, '--activate' => true]);
        $output = Artisan::output();
        $this->assertSame(0, $exitCode, $output);

        $model = PeticaoModelo::where('slug', 'modelo-de-teste-abc')->firstOrFail();
        $this->assertNotSame(500, (int) $model->id);
        $this->assertSame('ativo', $model->status);
        $this->assertNull($model->legacy_tipo_id);
        $this->assertSame(4, (int) $model->legacy_setor_id);
        $this->assertSame(3, (int) $model->legacy_cliente_id);
        $this->assertSame(54, (int) $model->legacy_sql_config_id);

        $fields = PeticaoModeloCampo::where('modelo_id', $model->id)->orderBy('ordem')->get();
        $this->assertCount(4, $fields);
        list($reu, $reu2, $valor, $tipo) = $fields->all();
        foreach ($fields as $field) {
            $this->assertGreaterThan(7000, $field->id);
            $this->assertNull($field->legacy_input_id);
            $this->assertSame('@modelo' . $model->id . '_campo' . $field->id . '@', $field->token);
        }

        // A lista CLIENTES tem outro ID neste ambiente.
        $this->assertSame('tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=9_|_vert', $reu->origem_alias);
        $this->assertSame(
            'fc_ajax_comp("tp_lista_tb","return_1","campo' . $reu2->id . '","unir","id_lista",this,1); '
            . 'mcampo("campo' . $reu->id . '_|_campo' . $reu2->id . '"); $("#campo' . $reu2->id . '").focus();',
            $reu->eventos_frontend['focus']
        );
        $this->assertSame([(int) $valor->id], $reu->eventos_frontend['conditional']['target_ids']);
        $this->assertTrue($reu->eventos_frontend['conditional']['required']);
        $this->assertSame('Ver @campo' . $reu->id . '@', $reu2->valor_padrao);
        $this->assertSame('repeteValor(' . $valor->id . ', ' . $reu2->id . ', this); fc_newstring(this);', $valor->eventos_frontend['blur']);

        $this->assertSame(
            '$(".dis_campo' . $valor->id . '").show(); fc_numextenso("' . $valor->id . '","' . $reu2->id . '","parcela"); '
            . 'mcampo("campo' . $tipo->id . '_|_campo658");',
            $tipo->eventos_frontend['focus']
        );
        // O campo 658 nao e deste modelo: fica como veio e a importacao avisa.
        $this->assertStringContainsString('campo658', $output);
        $this->assertStringContainsString('@campo9999@', $output);

        $options = $tipo->opcoes;
        $this->assertCount(2, $options);
        $this->assertSame('JUDICIAL', $options[0]->rotulo);
        $this->assertSame('Texto @campo' . $valor->id . '@', $options[0]->valor_retorno);
        $this->assertSame(['return_2' => 'J'], $options[0]->valores_extras);

        $this->assertSame('<p>Cabecalho @campo' . $reu->id . '@</p>', $model->cabecalho_html);
        $this->assertSame('Peticao @campo' . $reu2->id . '@', $model->metadata['nome_pre']);
        $this->assertSame(
            '<p>Reu: @campo' . $reu->id . '@ / @campo' . $reu2->id . '@ valor @campo' . $valor->id . '@ tipo @campo' . $tipo->id . '@ @campo9999@</p>',
            $model->paragrafos()->firstOrFail()->conteudo_html
        );

        $stored = json_encode([
            $model->fresh()->toArray(),
            $fields->toArray(),
            $model->paragrafos()->get()->toArray(),
            DB::table('peticao_modelo_campo_opcoes')->get()->all(),
        ]);
        $this->assertStringNotContainsString('{{field', $stored);

        // O que importa no fim: a peticao sai com os valores, sem marcador sobrando.
        $html = app(PeticaoComposerService::class)->compose($model->fresh(), [
            'campo_' . $reu->id => 'JOAO',
            'campo_' . $reu2->id => 'MARIA',
            'campo_' . $valor->id => '100',
            'campo_' . $tipo->id => 'EXTRAJUDICIAL',
        ])['html'];
        $this->assertStringContainsString('Reu: JOAO / MARIA valor 100 tipo Extra', $html);
        $this->assertStringContainsString('Cabecalho JOAO', $html);
    }

    public function test_marker_case_qualification_and_person_type_survive_the_transfer()
    {
        $this->seedDependencies();
        DB::table('peticao_modelos')->insert([
            'id' => 501, 'legacy_cliente_id' => 3, 'legacy_setor_id' => 4, 'legacy_sql_config_id' => 54,
            'nome' => 'INICIAL TESTE', 'slug' => 'inicial-teste', 'status' => 'ativo', 'arquivo_padrao' => 'pdf',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 901, 'modelo_id' => 501, 'titulo' => 'Corpo', 'ordem' => 1, 'visivel' => 1, 'ativo' => 1,
            'conteudo_html' => '<p>Em face de @CAMPO2103@, @campo2102@ @Campo2103@@campo2103_pos@</p>',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $base = ['modelo_id' => 501, 'legacy_input_id' => null, 'sufixo' => null, 'colunas_layout' => 1, 'linhas_layout' => 0, 'obrigatorio' => 1,
            'visivel' => 1, 'gera_nome_arquivo' => 0, 'created_at' => now(), 'updated_at' => now()];
        DB::table('peticao_modelo_campos')->insert([
            array_merge($base, ['id' => 2100, 'rotulo' => 'TIPO DE PESSOA', 'token' => '@campo2100@', 'tipo' => 'SELECT', 'ordem' => 1,
                'eventos_frontend' => json_encode(['focus' => 'alteraInput(this,2101,80,81);', 'dinamica' => ['tipo' => 'tipo_pessoa', 'alvo' => 2101]])]),
            array_merge($base, ['id' => 2101, 'rotulo' => 'CPF/CNPJ', 'token' => '@campo2101@', 'tipo' => 'TEXT', 'ordem' => 2, 'eventos_frontend' => '[]']),
            array_merge($base, ['id' => 2102, 'rotulo' => 'QUALIFICACAO', 'token' => '@campo2102@', 'tipo' => 'TEXT', 'ordem' => 3,
                'eventos_frontend' => json_encode(['focus' => 'merge_fields("pessoa |2100|2101","2102");', 'dinamica' => ['tipo' => 'qualificacao', 'pessoa' => 2100, 'documento' => 2101]])]),
            array_merge($base, ['id' => 2103, 'rotulo' => 'REU', 'token' => '@campo2103@', 'tipo' => 'TEXT', 'ordem' => 4, 'sufixo' => ' (reu)', 'eventos_frontend' => '[]']),
        ]);
        DB::table('peticao_modelo_campo_opcoes')->insert([
            ['id' => 80, 'campo_id' => 2100, 'legacy_dado_id' => null, 'rotulo' => 'FÍSICA', 'valor_retorno' => '', 'ordem' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 81, 'campo_id' => 2100, 'legacy_dado_id' => null, 'rotulo' => 'JURÍDICA', 'valor_retorno' => '', 'ordem' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $package = app(PeticaoModeloPackageService::class)->export(PeticaoModelo::findOrFail(501));
        $this->assertSame('<p>Em face de @CAMPO{{field_id:reu}}@, {{field:qualificacao}} @Campo{{field_id:reu}}@@campo{{field_id:reu}}_pos@</p>', $package['paragraphs'][0]['conteudo_html']);
        $this->assertSame('alteraInput(this,{{field_id:cpfcnpj}},{{option_id:tipo_de_pessoa:0}},{{option_id:tipo_de_pessoa:1}});', $package['fields'][0]['eventos_frontend']['focus']);
        $this->assertSame('merge_fields("pessoa |{{field_id:tipo_de_pessoa}}|{{field_id:cpfcnpj}}","{{field_id:qualificacao}}");', $package['fields'][2]['eventos_frontend']['focus']);
        $json = json_encode($package);
        foreach (['2100', '2101', '2102', '2103', '"80"', '"81"', ',80,', ',81)'] as $origin) {
            $this->assertStringNotContainsString($origin, $json);
        }

        $path = $this->writePackage($package);
        $this->turnIntoDestination();
        $this->assertSame(0, Artisan::call('peticao:modelo-stone-import', ['--path' => $path, '--activate' => true]), Artisan::output());

        $model = PeticaoModelo::where('slug', 'inicial-teste')->firstOrFail();
        list($pessoa, $documento, $qualificacao, $reu) = PeticaoModeloCampo::where('modelo_id', $model->id)->orderBy('ordem')->get()->all();
        $options = $pessoa->opcoes()->orderBy('ordem')->get();

        $this->assertSame('<p>Em face de @CAMPO' . $reu->id . '@, @campo' . $qualificacao->id . '@ @Campo' . $reu->id . '@@campo' . $reu->id . '_pos@</p>', $model->paragrafos()->firstOrFail()->conteudo_html);
        $this->assertSame('alteraInput(this,' . $documento->id . ',' . $options[0]->id_dados . ',' . $options[1]->id_dados . ');', $pessoa->eventos_frontend['focus']);
        $this->assertSame(['tipo' => 'tipo_pessoa', 'alvo' => (int) $documento->id], $pessoa->eventos_frontend['dinamica']);
        $this->assertSame('merge_fields("pessoa |' . $pessoa->id . '|' . $documento->id . '","' . $qualificacao->id . '");', $qualificacao->eventos_frontend['focus']);
        $this->assertSame(['tipo' => 'qualificacao', 'pessoa' => (int) $pessoa->id, 'documento' => (int) $documento->id], $qualificacao->eventos_frontend['dinamica']);

        // A forma do marcador continua valendo no destino.
        $html = app(PeticaoComposerService::class)->compose($model->fresh(), [
            'campo_' . $reu->id => 'joao da silva',
            'campo_' . $qualificacao->id => 'pessoa física',
        ])['html'];
        $this->assertStringContainsString('Em face de JOAO DA SILVA, pessoa física Joao da Silva (reu)', $html);
    }

    public function test_importing_again_updates_the_same_model_without_leaving_old_fields_behind()
    {
        $this->seedOrigin();
        $path = $this->writePackage(app(PeticaoModeloPackageService::class)->export(PeticaoModelo::findOrFail(500)));
        $this->turnIntoDestination();

        $this->assertSame(0, Artisan::call('peticao:modelo-stone-import', ['--path' => $path]));
        $model = PeticaoModelo::where('slug', 'modelo-de-teste-abc')->firstOrFail();
        $this->assertSame('inativo', $model->status);

        $this->assertSame(0, Artisan::call('peticao:modelo-stone-import', ['--path' => $path, '--activate' => true]));

        $this->assertSame(1, PeticaoModelo::where('slug', 'modelo-de-teste-abc')->count());
        $this->assertSame('ativo', $model->fresh()->status);
        $fieldIds = PeticaoModeloCampo::where('modelo_id', $model->id)->pluck('id')->all();
        $this->assertCount(4, $fieldIds);
        $this->assertSame(1, DB::table('peticao_modelo_paragrafos')->where('modelo_id', $model->id)->count());
        $this->assertSame(2, DB::table('peticao_modelo_campo_opcoes')->count());
        $this->assertSame(2, DB::table('peticao_modelo_campo_opcoes')->whereIn('campo_id', $fieldIds)->count());
    }

    public function test_dry_run_validates_without_writing()
    {
        $this->seedOrigin();
        $path = $this->writePackage(app(PeticaoModeloPackageService::class)->export(PeticaoModelo::findOrFail(500)));
        $this->turnIntoDestination();

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $path, '--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('RCJ - ATACADO', $output);
        $this->assertSame(0, PeticaoModelo::where('slug', 'modelo-de-teste-abc')->count());
    }

    public function test_old_package_with_field_numbers_of_the_origin_is_refused()
    {
        $this->seedDependencies();
        $path = $this->writePackage([
            'version' => 1,
            'dependencies' => ['client' => 'ACME', 'sector_name' => 'RCJ - ATACADO', 'server' => 'NEO'],
            'model' => ['nome' => 'Modelo antigo', 'slug' => 'modelo-antigo'],
            'fields' => [['key' => 'nome', 'rotulo' => 'NOME', 'tipo' => 'TEXT', 'ordem' => 1]],
            'paragraphs' => [['titulo' => 'Corpo', 'conteudo_html' => '<p>@campo2829@</p>', 'ordem' => 1]],
        ]);

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $path, '--activate' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('@campo2829@', $output);
        $this->assertStringContainsString('Exporte o modelo novamente', $output);
        $this->assertSame(0, PeticaoModelo::count());
        $this->assertSame(0, PeticaoModeloCampo::count());
    }

    public function test_sector_code_shared_by_two_sectors_is_not_guessed()
    {
        $this->seedDependencies();
        $package = $this->minimalPackage(['sector_code' => 'RCJ']);

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $this->writePackage($package)]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('RCJ - VAREJO', $output);
        $this->assertStringContainsString('RCJ - ATACADO', $output);
        $this->assertSame(0, PeticaoModelo::count());

        $package['dependencies']['sector_name'] = 'rcj - atacado';
        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $this->writePackage($package)]);
        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame(4, (int) PeticaoModelo::where('slug', 'modelo-minimo')->firstOrFail()->legacy_setor_id);
    }

    public function test_unique_sector_code_still_works_for_packages_without_the_sector_name()
    {
        $this->seedDependencies();

        $exitCode = Artisan::call('peticao:modelo-stone-import', [
            '--path' => $this->writePackage($this->minimalPackage(['sector_code' => 'CTR'])),
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame(5, (int) PeticaoModelo::where('slug', 'modelo-minimo')->firstOrFail()->legacy_setor_id);
    }

    public function test_unknown_sector_name_does_not_fall_back_to_the_code()
    {
        $this->seedDependencies();
        $package = $this->minimalPackage(['sector_name' => 'TRIBUTARIO', 'sector_code' => 'CTR']);

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $this->writePackage($package)]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('TRIBUTARIO', $output);
        $this->assertSame(0, PeticaoModelo::count());
    }

    public function test_client_with_repeated_name_is_chosen_by_code()
    {
        $this->seedDependencies();
        DB::table('clientes')->insert([
            'cliente_id' => 16, 'cliente_name' => 'ACME', 'cliente_cod' => 'ACME-VA', 'cliente_area' => 1, 'cliente_status' => 'Y',
        ]);
        $package = $this->minimalPackage(['sector_name' => 'RCJ - ATACADO', 'client' => 'ACME', 'client_code' => 'ACME-VA']);

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $this->writePackage($package)]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame(16, (int) PeticaoModelo::where('slug', 'modelo-minimo')->firstOrFail()->legacy_cliente_id);
    }

    public function test_missing_list_and_unknown_field_reference_block_the_import()
    {
        $this->seedDependencies();
        $package = $this->minimalPackage(['sector_name' => 'RCJ - ATACADO']);
        $package['fields'][0]['tipo'] = 'SELECT';
        $package['fields'][0]['origem_alias'] = 'tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=6_|_vert';
        $package['fields'][0]['lista_grupo'] = 'DADOS BANCARIOS';
        $package['paragraphs'][0]['conteudo_html'] = '<p>{{field:nome}} {{field:inexistente}}</p>';

        $exitCode = Artisan::call('peticao:modelo-stone-import', ['--path' => $this->writePackage($package)]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('DADOS BANCARIOS', $output);
        $this->assertStringContainsString('inexistente', $output);
        $this->assertSame(0, PeticaoModelo::count());
    }

    public function test_admin_downloads_portable_package_and_import_screen_shows_warnings()
    {
        $this->seedOrigin();
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);

        $response = $this->actingAs($admin)->get('/admin/modelos-normalizados/500/exportar');
        $response->assertStatus(200);
        $package = json_decode($response->streamedContent(), true);
        $this->assertSame(2, $package['version']);
        $this->assertSame('RCJ - ATACADO', $package['dependencies']['sector_name']);

        // No destino o cliente nao existe: o modelo entra sem cliente e a tela avisa.
        $this->turnIntoDestination();
        DB::table('clientes')->truncate();

        $import = $this->postPackage($admin, $package);

        $import->assertRedirect(route('admin.modelos-normalizados.index'));
        $import->assertSessionHasNoErrors();
        $this->assertStringContainsString('ACME', (string) session('status'));
        $model = PeticaoModelo::where('slug', 'modelo-de-teste-abc')->firstOrFail();
        $this->assertNull($model->legacy_cliente_id);
        $this->assertSame(4, (int) $model->legacy_setor_id);
    }

    public function test_import_screen_shows_why_a_package_was_refused()
    {
        $this->seedDependencies();
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);

        $import = $this->postPackage($admin, $this->minimalPackage(['sector_code' => 'RCJ']));

        $import->assertRedirect('/admin/modelos-normalizados');
        $import->assertSessionHasErrors('pacote');
        $this->assertStringContainsString('RCJ - ATACADO', session('errors')->first('pacote'));
        $this->assertSame(0, PeticaoModelo::count());
    }

    public function test_only_adm_sees_and_uses_model_export_and_import()
    {
        $this->seedOrigin();
        $manager = factory(User::class)->create(['nivel_usu' => 'GER', 'acesso_usu' => now()]);

        $page = $this->actingAs($manager)->get('/admin/modelos-normalizados');
        $page->assertStatus(200);
        $page->assertDontSee('Importar modelo');
        $page->assertDontSee('/exportar');
        $this->actingAs($manager)->get('/admin/modelos-normalizados/500/exportar')->assertStatus(403);
        $this->actingAs($manager)->post('/admin/modelos-normalizados/importar')->assertStatus(403);
    }

    public function test_adm_sees_model_export_and_import()
    {
        $this->seedOrigin();
        $admin = factory(User::class)->create(['nivel_usu' => 'ADM', 'acesso_usu' => now()]);

        $page = $this->actingAs($admin)->get('/admin/modelos-normalizados');
        $page->assertSee('Importar modelo');
        $page->assertSee('/exportar');
    }

    private function postPackage(User $admin, array $package)
    {
        $directory = storage_path('app/modelo-pacotes');
        $before = glob($directory . '/*.json') ?: [];
        $upload = new UploadedFile($this->writePackage($package), 'modelo.json', 'application/json', null, true);

        $response = $this->actingAs($admin)
            ->from('/admin/modelos-normalizados')
            ->post('/admin/modelos-normalizados/importar', ['pacote' => $upload]);

        // A tela guarda o arquivo enviado; o teste nao deve deixar o seu para tras.
        $this->files = array_merge($this->files, array_diff(glob($directory . '/*.json') ?: [], $before));

        return $response;
    }

    private function minimalPackage(array $dependencies)
    {
        return [
            'version' => 2,
            'dependencies' => $dependencies,
            'model' => ['nome' => 'Modelo minimo', 'slug' => 'modelo-minimo', 'arquivo_padrao' => 'pdf'],
            'fields' => [['key' => 'nome', 'rotulo' => 'NOME', 'tipo' => 'TEXT', 'ordem' => 1, 'options' => []]],
            'paragraphs' => [['titulo' => 'Corpo', 'conteudo_html' => '<p>{{field:nome}}</p>', 'ordem' => 1]],
        ];
    }

    private function writePackage(array $package)
    {
        $path = tempnam(sys_get_temp_dir(), 'pkg');
        file_put_contents($path, json_encode($package, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->files[] = $path;

        return $path;
    }

    /** Setores, cliente, servidor e lista: o que um modelo cita sem ser dono. */
    private function seedDependencies()
    {
        DB::table('setores')->insert([
            ['id_setor' => 1, 'nome_setor' => 'RCJ - VAREJO', 'cod_setor' => 'RCJ', 'data_cad' => now()],
            ['id_setor' => 4, 'nome_setor' => 'RCJ - ATACADO', 'cod_setor' => 'RCJ', 'data_cad' => now()],
            ['id_setor' => 5, 'nome_setor' => 'CTR - TRABALHISTA', 'cod_setor' => 'CTR', 'data_cad' => now()],
        ]);
        DB::table('clientes')->insert([
            'cliente_id' => 3, 'cliente_name' => 'ACME', 'cliente_cod' => 'ACME-AT', 'cliente_area' => 4, 'cliente_status' => 'Y',
        ]);
        DB::table('sql_server_profiles')->insert([
            'id' => 1, 'legacy_config_id' => 54, 'nome' => 'NEO', 'host' => 'localhost', 'database_name' => 'neo',
            'username' => 'leitura', 'status' => 'ativo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('lista_grupos')->insert([
            'id_grupo' => 2, 'legacy_grupo_id' => 2, 'nome_grupo' => 'CLIENTES', 'data_cad' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seedOrigin()
    {
        $this->seedDependencies();

        DB::table('peticao_modelos')->insert([
            'id' => 500,
            'legacy_tipo_id' => 85,
            'legacy_cliente_id' => 3,
            'legacy_setor_id' => 4,
            'legacy_sql_config_id' => 54,
            'nome' => 'MODELO DE TESTE',
            'slug' => 'modelo-de-teste-abc',
            'status' => 'ativo',
            'arquivo_padrao' => 'pdf',
            'cabecalho_html' => '<p>Cabecalho @campo1996@</p>',
            'rodape_html' => null,
            'metadata' => json_encode(['nome_pre' => 'Peticao @campo1997@']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('peticao_modelo_paragrafos')->insert([
            'id' => 900,
            'modelo_id' => 500,
            'legacy_fund_id' => 900,
            'titulo' => 'Corpo',
            'conteudo_html' => '<p>Reu: @campo1996@ / @campo1997@ valor @campo1998@ tipo @campo1999@ @campo9999@</p>',
            'ordem' => 1,
            'visivel' => 1,
            'ativo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listScript = 'fc_ajax_comp("tp_lista_tb","return_1","campo1997","unir","id_lista",this,1); '
            . 'mcampo("campo1996_|_campo1997"); $("#campo1997").focus();';

        $base = [
            'modelo_id' => 500, 'origem_alias' => null, 'valor_padrao' => null, 'colunas_layout' => 1, 'linhas_layout' => 0,
            'obrigatorio' => 1, 'visivel' => 1, 'gera_nome_arquivo' => 0, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('peticao_modelo_campos')->insert([
            // Dois campos com o mesmo rotulo: as chaves precisam ser diferentes.
            array_merge($base, [
                'id' => 1996, 'legacy_input_id' => 1996, 'rotulo' => 'NOME DO REU', 'token' => '@campo1996@', 'tipo' => 'SELECT',
                'origem_alias' => 'tp_lista_tb_|_nome_lista_|_return_1_|_id_grupo=2_|_vert', 'ordem' => 1,
                'eventos_frontend' => json_encode([
                    'focus' => $listScript,
                    'conditional' => ['value' => 'SIM', 'target_ids' => [1998], 'required' => true],
                ]),
            ]),
            array_merge($base, [
                'id' => 1997, 'legacy_input_id' => 1997, 'rotulo' => 'NOME DO REU', 'token' => '@campo1997@', 'tipo' => 'TEXT',
                'valor_padrao' => 'Ver @campo1996@', 'ordem' => 2, 'eventos_frontend' => json_encode([]),
            ]),
            // Campo criado ja no sistema novo: sem ID legado, o marcador usa o proprio ID.
            array_merge($base, [
                'id' => 1998, 'legacy_input_id' => null, 'rotulo' => 'VALOR', 'token' => '@modelo500_campo1998@', 'tipo' => 'TEXT',
                'ordem' => 3, 'eventos_frontend' => json_encode(['blur' => 'repeteValor(1998, 1997, this); fc_newstring(this);']),
            ]),
            // Formas menos comuns: classe .dis_campoN, numero entre aspas e um campo que nao e do modelo.
            array_merge($base, [
                'id' => 1999, 'legacy_input_id' => 1999, 'rotulo' => 'TIPO', 'token' => '@campo1999@', 'tipo' => 'SELECT',
                'ordem' => 4, 'eventos_frontend' => json_encode([
                    'focus' => '$(".dis_campo1998").show(); fc_numextenso("1998","1997","parcela"); mcampo("campo1999_|_campo658");',
                ]),
            ]),
        ]);

        DB::table('peticao_modelo_campo_opcoes')->insert([
            [
                'id' => 70, 'campo_id' => 1999, 'legacy_dado_id' => 70, 'rotulo' => 'JUDICIAL', 'valor_retorno' => 'Texto @campo1998@',
                'valores_extras' => json_encode(['return_2' => 'J']), 'ordem' => 1, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 71, 'campo_id' => 1999, 'legacy_dado_id' => 71, 'rotulo' => 'EXTRAJUDICIAL', 'valor_retorno' => 'Extra',
                'valores_extras' => null, 'ordem' => 2, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Simula o outro ambiente: o modelo ainda nao existe, a lista CLIENTES
     * tem outro ID e os proximos campos nascem com numeros diferentes.
     */
    private function turnIntoDestination()
    {
        foreach (['peticao_modelo_campo_opcoes', 'peticao_modelo_campos', 'peticao_modelo_paragrafos', 'peticao_modelos', 'lista_grupos'] as $table) {
            DB::table($table)->truncate();
        }

        DB::table('lista_grupos')->insert([
            ['id_grupo' => 2, 'legacy_grupo_id' => 2, 'nome_grupo' => 'LOCALIZADORES', 'data_cad' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['id_grupo' => 9, 'legacy_grupo_id' => 9, 'nome_grupo' => 'CLIENTES', 'data_cad' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('peticao_modelos')->insert([
            'id' => 40, 'nome' => 'Outro modelo', 'slug' => 'outro-modelo', 'status' => 'ativo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('peticao_modelo_campos')->insert([
            'id' => 7000, 'modelo_id' => 40, 'rotulo' => 'OUTRO', 'token' => '@modelo40_campo7000@', 'tipo' => 'TEXT',
            'ordem' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
