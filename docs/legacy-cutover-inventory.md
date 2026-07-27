# Inventario de corte fisico do legado

Data de referencia: 2026-07-27

## Estado atual

Hoje o runtime funcional do sistema esta assim:

- aplicacao principal: `laravel6/`
- raiz antiga: casca de compatibilidade + assets legados
- banco: legado + tabelas normalizadas

O codigo quente da aplicacao ja esta centrado no Laravel para:

- login
- painel
- montagem
- editor
- pecas salvas
- favoritos
- listas
- modelos normalizados
- servidores SQL normalizados

## Arquivos da raiz que ainda participam do runtime

### 1. Front controller e roteamento

Esses arquivos ainda participam diretamente da entrada HTTP:

- [`.htaccess`](C:/laragon/www/bvaa/peticaofacil/.htaccess)
- [`index.php`](C:/laragon/www/bvaa/peticaofacil/index.php)
- [`legacy_redirect_bootstrap.php`](C:/laragon/www/bvaa/peticaofacil/legacy_redirect_bootstrap.php)

Papel atual:

- roteiam assets legados
- deixam algumas URLs antigas funcionarem
- delegam o resto para `laravel6/public/index.php`

### 2. Wrappers PHP antigos ainda expostos

Depois da consolidacao da entrada unica, a raiz ficou reduzida a:

- [`index.php`](C:/laragon/www/bvaa/peticaofacil/index.php)
- [`legacy_redirect_bootstrap.php`](C:/laragon/www/bvaa/peticaofacil/legacy_redirect_bootstrap.php)
- [`login.php`](C:/laragon/www/bvaa/peticaofacil/login.php)
- [`sair.php`](C:/laragon/www/bvaa/peticaofacil/sair.php)

Estado atual:

- `admin.php`, `cliente.php`, `dados.php`, `editor.php`, `list.php`, `parag.php`, `pecas.php`, `setor.php`, `sql.php`, `usu.php` e `assinatura.php` ja foram removidos da raiz
- as URLs antigas agora entram por `index.php`
- `index.php` ainda tem a unica logica residual relevante de compatibilidade de POST legado com `hid_enviar`
- `login.php` e `sair.php` ainda participam do fluxo de sessao/bridge

### 3. Camada legado de sessao e bootstrap ainda ativa

Esses arquivos ainda sao usados por wrappers antigos:

- [`inc/seguranca.php`](C:/laragon/www/bvaa/peticaofacil/inc/seguranca.php)
- [`inc/bootstrap.php`](C:/laragon/www/bvaa/peticaofacil/inc/bootstrap.php)

Papel atual:

- sessao/cookie legado
- redirect para login moderno
- compatibilidade de bridge e logout
- bootstrap de env/DB do codigo antigo

### 4. Assets legados ainda servidos pela raiz

Pastas ainda publicadas pela raiz:

- [`public/ckeditor`](C:/laragon/www/bvaa/peticaofacil/public/ckeditor)
- [`public/ckfinder`](C:/laragon/www/bvaa/peticaofacil/public/ckfinder)
- [`public/css`](C:/laragon/www/bvaa/peticaofacil/public/css)
- [`public/js`](C:/laragon/www/bvaa/peticaofacil/public/js)
- [`public/img`](C:/laragon/www/bvaa/peticaofacil/public/img)
- [`public/inc`](C:/laragon/www/bvaa/peticaofacil/public/inc)

Observacao:

- CKEditor/CKFinder ainda sao dependencias reais do editor.
- esses assets precisam ser preservados ou realocados para o `public/` final do Laravel antes do corte fisico.

## Arquivos da raiz que ja sao candidatos a remocao futura

Esses arquivos nao devem ser apagados agora, mas ja sao candidatos claros:

- `admin.php`
- `cliente.php`
- `dados.php`
- `editor.php`
- `list.php`
- `parag.php`
- `pecas.php`
- `setor.php`
- `sql.php`
- `usu.php`
- `assinatura.php`

Condicao para remocao:

- as URLs antigas equivalentes estarem cobertas por rota Laravel ou redirect de servidor

## Tabelas legadas que ainda existem, mas ja perderam o papel principal

### Ja fora do runtime normal

- `tp_pecas_tb`
- `tp_tipo_tb`
- `tp_funda_tb`
- `tp_inputs_tb`
- `tp_dados_tb`

Papel atual:

- `peticao:sync-legado`
- `peticao:sync-pecas`
- sincronizacao manual
- rollback controlado
- compatibilidade administrativa residual

### Ainda com dependencia residual relevante

- `tp_usu_tb`
- `tp_grupo_tb`
- `tp_lista_tb`
- `tp_config_db`

Essas ainda exigem corte completo para o estado final.

## Plano de consolidacao fisica

### Fase A: raiz minima de compatibilidade

Objetivo:

- deixar a raiz apenas com o minimo necessario para entrada e redirect

Estado atual desta fase:

- `.htaccess`
- `index.php`
- `legacy_redirect_bootstrap.php`
- `login.php`
- `sair.php`
- `inc/seguranca.php`
- `inc/bootstrap.php`
- `public/ckeditor`
- `public/ckfinder`
- `public/img`

Os wrappers antigos de navegacao ja sairam.

### Fase B: promocao de `laravel6/` para raiz

Objetivo:

- transformar a raiz do repositorio na estrutura oficial do Laravel

Movimentos previstos:

- mover conteudo de `laravel6/app` para `app`
- mover `laravel6/bootstrap` para `bootstrap`
- mover `laravel6/config` para `config`
- mover `laravel6/database` para `database`
- mover `laravel6/public` para `public`
- mover `laravel6/resources` para `resources`
- mover `laravel6/routes` para `routes`
- mover `laravel6/storage` para `storage`
- mover `laravel6/tests` para `tests`
- mover `laravel6/artisan` para `artisan`
- mover `laravel6/composer.json` para `composer.json`
- mover `laravel6/.env*` para a raiz

Ajustes necessarios:

- paths de `bootstrap/app.php`
- paths do front controller `public/index.php`
- paths do `artisan`
- `.htaccess` final
- referencias a assets de CKEditor/CKFinder

### Fase C: aposentadoria da casca antiga

Objetivo:

- remover wrappers PHP antigos
- manter no maximo regras de redirect no servidor

Alvos de exclusao:

- `admin.php`
- `cliente.php`
- `dados.php`
- `editor.php`
- `list.php`
- `parag.php`
- `pecas.php`
- `setor.php`
- `sql.php`
- `usu.php`
- `assinatura.php`
- `legacy_redirect_bootstrap.php`
- `login.php`
- `sair.php`
- `inc/seguranca.php`
- `inc/bootstrap.php`

### Fase D: retirada final das tabelas legadas

So depois de:

- zero leitura no runtime
- zero escrita no runtime
- backfill fechado
- homologacao validada
- backup confirmado

Remocoes previstas por modulo:

- usuarios: `tp_usu_tb`
- listas: `tp_grupo_tb`, `tp_lista_tb`
- pecas: `tp_pecas_tb`
- modelos: `tp_tipo_tb`, `tp_funda_tb`, `tp_inputs_tb`, `tp_dados_tb`
- SQL Server: `tp_config_db`

## Proximo passo tecnico recomendado

Antes de mover `laravel6/` para a raiz:

1. reduzir a raiz para front controller + bridge + assets essenciais
2. trocar wrappers antigos por redirects de servidor quando possivel
3. copiar CKEditor/CKFinder e imagens para o `public/` definitivo do Laravel
4. so entao promover fisicamente `laravel6/` para a raiz
