# Plano de promocao de `laravel6/` para a raiz

Data de referencia: 2026-07-27

## Objetivo

Transformar a raiz do repositorio em um projeto Laravel unico, eliminando a estrutura atual:

- raiz antiga = compatibilidade
- `laravel6/` = aplicacao principal

Estado alvo:

- raiz = aplicacao Laravel oficial
- `public/` da raiz = `public/` oficial do Laravel + assets legados ainda necessarios
- wrappers PHP antigos removidos

## Status atual

Esta promocao ja foi executada.

Hoje:

- a raiz e o runtime oficial do Laravel
- `public/` final ja preserva `ckeditor/`, `ckfinder/` e `img/`
- a antiga casca PHP ficou reduzida a compatibilidade residual por rota

## Pre-condicoes

Antes da promocao fisica:

1. runtime principal ja precisa estar 100% no Laravel
2. compatibilidade antiga precisa estar reduzida ao minimo
3. `.env` do Laravel precisa ser a referencia oficial
4. assets legados ainda usados pelo editor precisam estar preservados

## Mapeamento de movimentacao

### Diretorios do Laravel que sobem para a raiz

Origem `laravel6/` -> destino raiz:

- `laravel6/app` -> `app`
- `laravel6/bootstrap` -> `bootstrap`
- `laravel6/config` -> `config`
- `laravel6/database` -> `database`
- `laravel6/public` -> `public`
- `laravel6/resources` -> `resources`
- `laravel6/routes` -> `routes`
- `laravel6/storage` -> `storage`
- `laravel6/tests` -> `tests`
- `laravel6/vendor` -> `vendor`

### Arquivos do Laravel que sobem para a raiz

- `laravel6/artisan` -> `artisan`
- `laravel6/composer.json` -> `composer.json`
- `laravel6/composer.lock` -> `composer.lock`
- `laravel6/package.json` -> `package.json`
- `laravel6/package-lock.json` -> `package-lock.json`
- `laravel6/phpunit.xml` -> `phpunit.xml`
- `laravel6/server.php` -> `server.php`
- `laravel6/webpack.mix.js` -> `webpack.mix.js`
- `laravel6/.env` -> `.env`
- `laravel6/.env.example` -> `.env.example`

## Ajustes obrigatorios por arquivo

### 1. `public/`

Depois da promocao, a raiz publica oficial deve ser a `public/` do Laravel.

Isso significa:

- o `public/index.php` oficial sera o do Laravel
- o `.htaccess` publico oficial sera o do Laravel
- a raiz do projeto nao deve mais servir a aplicacao por `index.php` fora de `public/`

### 2. `bootstrap/app.php`

Hoje:
- a raiz possui um `bootstrap/app.php` legado
- o Laravel possui `laravel6/bootstrap/app.php`

Na promocao:
- o `bootstrap/app.php` legado deve sair
- o `bootstrap/app.php` oficial passa a ser o do Laravel

### 3. `artisan`

Na promocao:
- o `artisan` oficial passa a ficar na raiz
- qualquer script operacional deve passar a rodar no cwd da raiz, nao mais em `laravel6/`

### 4. `.env`

Na promocao:
- o `.env` oficial deve ficar na raiz
- referencias como:
  - `LARAVEL_APP_URL`
  - `LEGACY_APP_URL`
  - `LEGACY_CKFINDER_ROOT`
  - `LEGACY_CKFINDER_BASE_URL`
devem ser revistas para refletir o novo `public/` final

### 5. `composer.json`

Hoje existe conflito de intencao:

- raiz atual:
  - [`composer.json`](C:/laragon/www/bvaa/peticaofacil/composer.json)
  - bootstrap legado incremental
- Laravel:
  - [`laravel6/composer.json`](C:/laragon/www/bvaa/peticaofacil/laravel6/composer.json)

Na promocao:
- o `composer.json` oficial deve ser o do Laravel
- o composer legado da raiz deve sair

### 6. `.htaccess`

Hoje:
- a raiz tem um `.htaccess` de compatibilidade
- o Laravel tem `laravel6/public/.htaccess`

Na promocao:
- o `.htaccess` operacional deve ficar dentro do `public/` final
- o `.htaccess` da raiz antiga deixa de ser o mecanismo principal da aplicacao

## O que precisa ser preservado da `public/` antiga

### Preservacao obrigatoria

Esses assets ainda sao usados pelo runtime atual do Laravel:

1. `public/ckeditor/`
   - usado nas views:
     - `resources/views/admin/tipos/form.blade.php`
     - `resources/views/peticao/editor.blade.php`
     - `resources/views/peticao/saved-editor.blade.php`

2. `public/ckfinder/`
   - usado junto com CKEditor
   - inclui `userfiles/`, que contem arquivos reais do sistema

3. `public/img/`
   - contem imagens reais referenciadas em HTML legado e conteudo de peticoes
   - a normalizacao atual ainda resolve imagens para esse caminho

### Preservacao provavel, mas nao necessariamente permanente

4. `public/css/`
5. `public/js/`
6. `public/inc/`

Esses itens parecem muito mais ligados ao layout/superficie antiga. Antes de copiar para o `public/` final, o correto e validar se ainda ha referencia viva no Laravel.

Se nao houver referencia real no runtime novo:
- nao devem subir como dependencia permanente
- podem ir para arquivo/historico

## Estrategia recomendada para `public/`

### Etapa 1: montar `public/` final do Laravel

Base:
- `laravel6/public/*`

Adicionar por cima apenas o necessario da `public/` antiga:

- `ckeditor/`
- `ckfinder/`
- `img/`

### Etapa 2: revisar configuracao

Depois da promocao:

- `LEGACY_CKFINDER_ROOT` deve apontar para:
  - `.../public/ckfinder/userfiles/`
- `LEGACY_CKFINDER_BASE_URL` deve apontar para:
  - `/peticaofacil/ckfinder/userfiles/`
  ou a URL final equivalente no novo host/base path

### Etapa 3: validar editor

Validar:

- editor de peticao
- editor salvo
- admin de modelos
- upload/navegacao de arquivos no CKFinder
- imagens antigas embutidas em HTML

## Ordem recomendada da promocao

### Fase 1: congelar a casca antiga

- manter apenas:
  - front controller minimo da raiz
  - bridge residual estritamente necessaria
- nenhuma nova funcionalidade entra fora do Laravel

### Fase 2: preparar `public/` final

- copiar `laravel6/public` para staging de promocao
- incorporar:
  - `ckeditor/`
  - `ckfinder/`
  - `img/`

### Fase 3: promover o Laravel

- mover estrutura de `laravel6/` para a raiz
- substituir:
  - `composer.json`
  - `bootstrap/app.php`
  - `artisan`
  - `.env`

### Fase 4: ajustar entrada HTTP

- o webroot deve apontar para `public/`
- remover dependencia do `index.php` antigo da raiz
- remover `.htaccess` de compatibilidade da raiz

### Fase 5: apagar casca antiga

So depois da validacao:

## Proximo passo

- continuar o corte das compatibilidades residuais
- reduzir as rotas `legacy.*` restantes a compatibilidade estrita
- concluir a retirada das tabelas legadas do runtime

- remover a duplicata `laravel6/`
- limpar qualquer resquicio residual fora do runtime
- manter apenas backup datado em `_legacy_backup`

## Riscos tecnicos principais

1. `public/ckfinder/userfiles` contem dado real
   - nao pode ser perdido nem sobrescrito incorretamente

2. `public/img` contem imagens antigas referenciadas em HTML salvo
   - se o caminho mudar sem compatibilidade, quebram imagens em peticoes antigas

3. `composer.json` da raiz atual nao pode sobreviver como oficial
   - ele descreve o bootstrap legado, nao a aplicacao final

4. `bootstrap/app.php` legado e `bootstrap/app.php` do Laravel nao podem coexistir como se tivessem o mesmo papel

## Estado atual

Promocao fisica executada:

- a raiz ja e o runtime principal do Laravel
- `public/` final ja foi montado com:
  - `ckeditor/`
  - `ckfinder/`
  - `img/`
- `public/css`, `public/js` e `public/inc` antigos ficaram fora da promocao
- a base local ja opera com tabelas legadas arquivadas por rename controlado

Pendencia restante:

1. seguir no corte final da compatibilidade residual por rotas `legacy.*`
2. validar a mesma politica de arquivo/remocao em homologacao e producao
