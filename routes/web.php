<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController')->name('home');

Route::view('/status', 'status')->name('status');

Route::middleware('guest')->group(function () {
    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login', 'Auth\LoginController@login')->name('login.attempt');
});

Route::middleware(['auth', 'legacy.password.change'])->group(function () {
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
    Route::get('/primeiro-acesso', 'Auth\LoginController@showForcePasswordForm')->name('password.force');
    Route::post('/primeiro-acesso', 'Auth\LoginController@updateForcedPassword')->name('password.force.update');
    Route::get('/painel', 'DashboardController')->name('dashboard');
    Route::get('/estatisticas', 'EstatisticasController')->name('estatisticas');
    Route::get('/peticoes', 'PeticaoAssemblyController@index')->name('peticoes.index');
    Route::get('/peticoes-avulsas/criar', 'PeticaoAvulsaController@create')->name('peticoes.avulsas.create');
    Route::post('/peticoes-avulsas', 'PeticaoAvulsaController@store')->name('peticoes.avulsas.store');
    Route::get('/assistente-peticoes', 'PeticaoAssistantController@index')->name('peticoes.assistente.index');
    Route::post('/assistente-peticoes/mensagem', 'PeticaoAssistantController@message')->name('peticoes.assistente.message');
    Route::post('/assistente-peticoes/campo-atual', 'PeticaoAssistantController@answerCurrentField')->name('peticoes.assistente.answer-current-field');
    Route::post('/assistente-peticoes/reiniciar', 'PeticaoAssistantController@reset')->name('peticoes.assistente.reset');
    Route::post('/assistente-peticoes/modelos/{modeloNormalizado}', 'PeticaoAssistantController@selectModel')->name('peticoes.assistente.select-model');
    Route::post('/peticoes/modelos/{modeloNormalizado}/favorito', 'FavoriteModeloController@storeNormalized')->name('peticoes.normalized.favorite.store');
    Route::delete('/peticoes/modelos/{modeloNormalizado}/favorito', 'FavoriteModeloController@destroyNormalized')->name('peticoes.normalized.favorite.destroy');
    Route::get('/peticoes/modelos/{modeloNormalizado}', 'PeticaoAssemblyController@showNormalized')->name('peticoes.normalized.show');
    Route::post('/peticoes/modelos/{modeloNormalizado}', 'PeticaoAssemblyController@composeNormalized')->name('peticoes.normalized.compose');
    Route::post('/peticoes/modelos/{modeloNormalizado}/editor', 'PeticaoEditorController@createNormalized')->name('peticoes.normalized.editor.create');
    Route::post('/peticoes/modelos/{modeloNormalizado}/salvar', 'PeticaoEditorController@saveNormalized')->name('peticoes.normalized.editor.save');
    Route::post('/peticoes/modelos/{modeloNormalizado}/exportar/pdf', 'PeticaoEditorController@exportNormalizedPdf')->name('peticoes.normalized.editor.export.pdf');
    Route::post('/peticoes/modelos/{modeloNormalizado}/exportar/word', 'PeticaoEditorController@exportNormalizedWord')->name('peticoes.normalized.editor.export.word');
    Route::post('/peticoes/modelos/{modeloNormalizado}/peticao-normalizada', 'PeticaoSavedController@storeFromNormalizedPreview')->name('peticoes.normalized.saved.store');
    Route::get('/peticoes-salvas/{peticao}/editar', 'PeticaoSavedController@edit')->name('peticoes.saved.edit');
    Route::put('/peticoes-salvas/{peticao}', 'PeticaoSavedController@update')->name('peticoes.saved.update');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/restaurar', 'PeticaoSavedController@restoreVersion')->name('peticoes.saved.versions.restore');
    Route::get('/peticoes-salvas/{peticao}/versoes/{versao}/comparar', 'PeticaoSavedController@compareVersions')->name('peticoes.saved.versions.compare');
    Route::get('/peticoes-salvas/{peticao}/versoes/{versao}/impressao', 'PeticaoSavedController@printVersion')->name('peticoes.saved.versions.print');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/exportar/word', 'PeticaoSavedController@exportVersionWord')->name('peticoes.saved.versions.export.word');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/exportar/pdf', 'PeticaoSavedController@exportVersionPdf')->name('peticoes.saved.versions.export.pdf');
    Route::get('/peticoes-salvas/{peticao}/impressao', 'PeticaoSavedController@print')->name('peticoes.saved.print');
    Route::post('/peticoes-salvas/{peticao}/importar/word', 'PeticaoSavedController@importWord')->name('peticoes.saved.import.word');
    Route::post('/peticoes-salvas/{peticao}/revisar', 'PeticaoSavedController@review')->name('peticoes.saved.review');
    Route::post('/peticoes-salvas/{peticao}/revisao/languagetool/preferencias', 'PeticaoSavedController@storeLanguageToolPreference')->name('peticoes.saved.review.preferences.store');
    Route::post('/peticoes-salvas/{peticao}/revisao/ia', 'PeticaoSavedController@reviewWithAi')->name('peticoes.saved.review.ai');
    Route::post('/peticoes-salvas/{peticao}/exportar/pdf', 'PeticaoSavedController@exportPdf')->name('peticoes.saved.export.pdf');
    Route::post('/peticoes-salvas/{peticao}/exportar/word', 'PeticaoSavedController@exportWord')->name('peticoes.saved.export.word');
    Route::get('/pecas', 'PecaController@index')->name('pecas.index');

    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::resource('usuarios', 'Admin\UserController')->except(['show', 'destroy'])->parameters([
            'usuarios' => 'user',
        ]);
        Route::resource('setores', 'Admin\SetorController')->except(['show', 'destroy'])->parameters([
            'setores' => 'setor',
        ]);
        Route::resource('clientes', 'Admin\ClienteController')->except(['show', 'destroy'])->parameters([
            'clientes' => 'cliente',
        ]);
        Route::redirect('servidores', 'admin/servidores-normalizados')->name('servidores.index');
        Route::get('servidores-normalizados', 'Admin\NormalizedSqlServerConfigController@index')->name('servidores-normalizados.index');
        Route::get('servidores-normalizados/create', 'Admin\NormalizedSqlServerConfigController@create')->name('servidores-normalizados.create');
        Route::post('servidores-normalizados', 'Admin\NormalizedSqlServerConfigController@store')->name('servidores-normalizados.store');
        Route::get('servidores-normalizados/{servidorNormalizado}/edit', 'Admin\NormalizedSqlServerConfigController@edit')->name('servidores-normalizados.edit');
        Route::put('servidores-normalizados/{servidorNormalizado}', 'Admin\NormalizedSqlServerConfigController@update')->name('servidores-normalizados.update');
        Route::redirect('modelos', 'admin/modelos-normalizados')->name('modelos.index');
        Route::get('modelos-normalizados', 'Admin\NormalizedTipoController@index')->name('modelos-normalizados.index');
        Route::get('modelos-normalizados/create', 'Admin\NormalizedTipoController@create')->name('modelos-normalizados.create');
        Route::post('modelos-normalizados', 'Admin\NormalizedTipoController@store')->name('modelos-normalizados.store');
        Route::get('modelos-normalizados/{modeloNormalizado}/edit', 'Admin\NormalizedTipoController@edit')->name('modelos-normalizados.edit');
        Route::put('modelos-normalizados/{modeloNormalizado}', 'Admin\NormalizedTipoController@update')->name('modelos-normalizados.update');
        Route::get('peticoes-avulsas/configuracao', 'Admin\PeticaoAvulsaConfigController@edit')->name('peticoes-avulsas.config.edit');
        Route::put('peticoes-avulsas/configuracao', 'Admin\PeticaoAvulsaConfigController@update')->name('peticoes-avulsas.config.update');
        Route::post('modelos-normalizados/{modeloNormalizado}/paragrafos', 'Admin\NormalizedParagrafoController@store')->name('modelos-normalizados.paragrafos.store');
        Route::put('modelos-normalizados/{modeloNormalizado}/paragrafos/{paragrafo}', 'Admin\NormalizedParagrafoController@update')->name('modelos-normalizados.paragrafos.update');
        Route::post('modelos-normalizados/{modeloNormalizado}/campos', 'Admin\NormalizedInputCampoController@store')->name('modelos-normalizados.campos.store');
        Route::put('modelos-normalizados/{modeloNormalizado}/campos/{campo}', 'Admin\NormalizedInputCampoController@update')->name('modelos-normalizados.campos.update');
        Route::resource('listas', 'Admin\ListaController')->except(['show'])->parameters([
            'listas' => 'lista',
        ]);
        Route::get('listas/{lista}/itens/create', 'Admin\ListaItemController@create')->name('listas.itens.create');
        Route::post('listas/{lista}/itens', 'Admin\ListaItemController@store')->name('listas.itens.store');
        Route::get('listas/{lista}/itens/{item}/edit', 'Admin\ListaItemController@edit')->name('listas.itens.edit');
        Route::put('listas/{lista}/itens/{item}', 'Admin\ListaItemController@update')->name('listas.itens.update');
        Route::delete('listas/{lista}/itens/{item}', 'Admin\ListaItemController@destroy')->name('listas.itens.destroy');
    });
});
