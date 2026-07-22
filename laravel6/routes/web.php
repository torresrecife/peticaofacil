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
    Route::get('/peticoes/{modelo}', 'PeticaoAssemblyController@show')->name('peticoes.show');
    Route::post('/peticoes/{modelo}', 'PeticaoAssemblyController@compose')->name('peticoes.compose');
    Route::post('/peticoes/{modelo}/editor', 'PeticaoEditorController@create')->name('peticoes.editor.create');
    Route::post('/peticoes/{modelo}/salvar', 'PeticaoEditorController@save')->name('peticoes.editor.save');
    Route::post('/peticoes/{modelo}/exportar/pdf', 'PeticaoEditorController@exportPdf')->name('peticoes.editor.export.pdf');
    Route::post('/peticoes/{modelo}/exportar/word', 'PeticaoEditorController@exportWord')->name('peticoes.editor.export.word');
    Route::get('/pecas/{peca}/editar', 'PeticaoEditorController@edit')->name('peticoes.editor.edit');
    Route::get('/pecas', 'PecaController@index')->name('pecas.index');

    Route::prefix('admin')->name('admin.')->middleware('legacy.role:ADM,GER')->group(function () {
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
