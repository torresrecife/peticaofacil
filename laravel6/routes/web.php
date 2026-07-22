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

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::view('/status', 'status')->name('status');
Route::get('/legacy/bridge', 'Auth\LegacyBridgeController')->name('legacy.bridge');
Route::get('/legacy/logout', 'Auth\LoginController@logoutBridge')->name('legacy.logout');

Route::middleware('guest')->group(function () {
    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login', 'Auth\LoginController@login')->name('login.attempt');
});

Route::middleware(['auth', 'legacy.password.change'])->group(function () {
    Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
    Route::get('/primeiro-acesso', 'Auth\LoginController@showForcePasswordForm')->name('password.force');
    Route::post('/primeiro-acesso', 'Auth\LoginController@updateForcedPassword')->name('password.force.update');
    Route::get('/painel', 'DashboardController')->name('dashboard');
    Route::get('/peticoes', 'PeticaoAssemblyController@index')->name('peticoes.index');
    Route::get('/peticoes/modelos/{modeloNormalizado}', 'PeticaoAssemblyController@showNormalized')->name('peticoes.normalized.show');
    Route::post('/peticoes/modelos/{modeloNormalizado}', 'PeticaoAssemblyController@composeNormalized')->name('peticoes.normalized.compose');
    Route::get('/peticoes/{modelo}', 'PeticaoAssemblyController@show')->name('peticoes.show');
    Route::post('/peticoes/{modelo}', 'PeticaoAssemblyController@compose')->name('peticoes.compose');
    Route::post('/peticoes/{modelo}/editor', 'PeticaoEditorController@create')->name('peticoes.editor.create');
    Route::post('/peticoes/modelos/{modeloNormalizado}/peticao-normalizada', 'PeticaoSavedController@storeFromNormalizedPreview')->name('peticoes.normalized.saved.store');
    Route::post('/peticoes/{modelo}/peticao-normalizada', 'PeticaoSavedController@storeFromPreview')->name('peticoes.saved.store');
    Route::post('/peticoes/{modelo}/salvar', 'PeticaoEditorController@save')->name('peticoes.editor.save');
    Route::post('/peticoes/{modelo}/exportar/pdf', 'PeticaoEditorController@exportPdf')->name('peticoes.editor.export.pdf');
    Route::post('/peticoes/{modelo}/exportar/word', 'PeticaoEditorController@exportWord')->name('peticoes.editor.export.word');
    Route::get('/peticoes-salvas/{peticao}/editar', 'PeticaoSavedController@edit')->name('peticoes.saved.edit');
    Route::put('/peticoes-salvas/{peticao}', 'PeticaoSavedController@update')->name('peticoes.saved.update');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/restaurar', 'PeticaoSavedController@restoreVersion')->name('peticoes.saved.versions.restore');
    Route::get('/peticoes-salvas/{peticao}/versoes/{versao}/comparar', 'PeticaoSavedController@compareVersions')->name('peticoes.saved.versions.compare');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/exportar/word', 'PeticaoSavedController@exportVersionWord')->name('peticoes.saved.versions.export.word');
    Route::post('/peticoes-salvas/{peticao}/versoes/{versao}/exportar/pdf', 'PeticaoSavedController@exportVersionPdf')->name('peticoes.saved.versions.export.pdf');
    Route::post('/peticoes-salvas/{peticao}/exportar/pdf', 'PeticaoSavedController@exportPdf')->name('peticoes.saved.export.pdf');
    Route::post('/peticoes-salvas/{peticao}/exportar/word', 'PeticaoSavedController@exportWord')->name('peticoes.saved.export.word');
    Route::get('/pecas/{peca}/editar', 'PeticaoEditorController@edit')->name('peticoes.editor.edit');
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
        Route::resource('servidores', 'Admin\SqlServerConfigController')->except(['show', 'destroy'])->parameters([
            'servidores' => 'servidore',
        ]);
        Route::resource('modelos', 'Admin\TipoController')->except(['show', 'destroy'])->parameters([
            'modelos' => 'modelo',
        ]);
        Route::resource('listas', 'Admin\ListaController')->except(['show'])->parameters([
            'listas' => 'lista',
        ]);
        Route::post('modelos/{modelo}/paragrafos', 'Admin\ParagrafoController@store')->name('modelos.paragrafos.store');
        Route::put('modelos/{modelo}/paragrafos/{paragrafo}', 'Admin\ParagrafoController@update')->name('modelos.paragrafos.update');
        Route::post('modelos/{modelo}/campos', 'Admin\InputCampoController@store')->name('modelos.campos.store');
        Route::put('modelos/{modelo}/campos/{campo}', 'Admin\InputCampoController@update')->name('modelos.campos.update');
    });
});
