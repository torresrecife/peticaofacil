# Inventario de corte fisico do legado

Data de referencia: 2026-07-27

## Estado atual

Hoje o runtime funcional do sistema esta assim:

- aplicacao principal: raiz do projeto
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

Papel atual:

- roteiam assets legados ainda preservados
- deixam URLs residuais como `login.php` e `sair.php` entrarem por rota Laravel
- delegam o resto para `public/index.php`

### 2. Wrappers PHP antigos ainda expostos

Depois da promocao fisica, a raiz ficou reduzida a:

- [`index.php`](C:/laragon/www/bvaa/peticaofacil/index.php)
- [`.htaccess`](C:/laragon/www/bvaa/peticaofacil/.htaccess)

Estado atual:

- `admin.php`, `cliente.php`, `dados.php`, `editor.php`, `list.php`, `parag.php`, `pecas.php`, `setor.php`, `sql.php`, `usu.php` e `assinatura.php` ja foram removidos da raiz
- `login.php` e `sair.php` tambem ja sairam como arquivos fisicos
- essas URLs agora sao atendidas por rotas Laravel:
  - `/login.php`
  - `/sair.php`

### 3. Camada legado PHP residual

Na raiz ativa, essa camada ja nao participa mais do runtime.

O que restou dela esta no backup local:

- [`_legacy_backup`](C:/laragon/www/bvaa/peticaofacil/_legacy_backup)

Compatibilidade residual ainda ativa no runtime:

- rota `/login.php` -> redirect Laravel para `/login`
- rota `/sair.php` -> logout compatível via controller Laravel
- rotas `legacy.*` de modelos e pecas, ja tratadas como compatibilidade explicita

A antiga bridge de sessao (`/legacy/bridge`) ja saiu do runtime.

### 4. Assets legados ainda servidos pela raiz

Pastas ainda publicadas pela raiz:

- [`public/ckeditor`](C:/laragon/www/bvaa/peticaofacil/public/ckeditor)
- [`public/ckfinder`](C:/laragon/www/bvaa/peticaofacil/public/ckfinder)
- [`public/img`](C:/laragon/www/bvaa/peticaofacil/public/img)

Observacao:

- CKEditor/CKFinder ainda sao dependencias reais do editor.
- esses assets ja foram preservados no `public/` final do Laravel.

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
- `public/ckeditor`
- `public/ckfinder`
- `public/img`

Os wrappers antigos de navegacao ja sairam.
`login.php` e `sair.php` foram absorvidos por rotas Laravel.

### Fase B: promocao de `laravel6/` para raiz

Objetivo:

- transformar a raiz do repositorio na estrutura oficial do Laravel

Status:

- concluida

Movimentos executados:

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

Pendencia atual:

1. seguir no corte final das tabelas legadas e compatibilidades residuais
2. decidir quando as rotas `legacy.*` restantes podem virar redirect estrito ou ser removidas
