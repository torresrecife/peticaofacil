# Petição Fácil

Sistema interno para criação, edição, revisão, versionamento e exportação de petições jurídicas. A aplicação administra modelos reutilizáveis, parágrafos, campos dinâmicos, listas de opções, clientes, setores e integrações com fontes externas.

O projeto é uma aplicação Laravel autônoma, com editor de documentos, administração do conteúdo jurídico e integrações opcionais.

## Funcionalidades

- autenticação, primeiro acesso e níveis de usuário;
- painel com favoritos e resumo da produção diária;
- cadastro de usuários, setores e clientes;
- administração de modelos, parágrafos e campos dinâmicos;
- listas de opções e retornos associados aos campos;
- montagem orientada de petições;
- petição avulsa;
- assistente de montagem com integração opcional à OpenAI;
- consulta opcional de processos em sistemas jurídicos ou outras fontes externas;
- editor rico local com CKEditor e CKFinder;
- salvamento e histórico de versões;
- comparação e restauração de versões;
- revisão com LanguageTool e revisão opcional por IA;
- importação de documentos Word com LibreOffice;
- exportação para DOCX e PDF;
- estatísticas operacionais.

## Arquitetura atual

- PHP `^7.2.5` ou `^8.0`;
- Laravel `6.20`;
- MySQL;
- conectores configuráveis para consultas a sistemas externos;
- PHPWord para DOCX;
- Playwright/Chromium para PDF;
- CKEditor e CKFinder servidos por `public/`;
- Laravel Mix 5 para os assets compilados.

As tabelas centrais são:

- `users`;
- `setores` e `clientes`;
- `peticao_modelos`;
- `peticao_modelo_paragrafos`;
- `peticao_modelo_campos`;
- `peticao_modelo_campo_opcoes`;
- `lista_grupos` e `lista_itens`;
- `peticoes` e `peticao_versoes`;
- `user_model_favorites`;
- `user_languagetool_preferences`.

## Requisitos

- PHP com extensões exigidas pelo Laravel, MySQL, DOM/XML, cURL, Mbstring, Zip e GD;
- Composer;
- MySQL;
- Node.js e npm;
- Chromium, Chrome ou Edge para geração de PDF;
- LibreOffice, caso a importação de Word seja utilizada;
- acesso aos sistemas externos, OpenAI e LanguageTool somente quando essas integrações forem habilitadas.

No ambiente Windows usado no desenvolvimento, o projeto funciona com Laragon e PHP 7.2.34. Em instalações novas, prefira uma versão de PHP ainda suportada e valide previamente a compatibilidade do Laravel 6.

## Instalação local

Clone o repositório e instale as dependências PHP:

```bash
composer install
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

No PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure no mínimo `APP_URL` e as variáveis `DB_*`. O banco atualmente utiliza:

```env
DB_CHARSET=latin1
DB_COLLATION=latin1_swedish_ci
```

Não altere o charset diretamente em produção. A futura conversão para `utf8mb4` precisa de migration e auditoria dos textos existentes.

Depois, execute:

```bash
php artisan migrate
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Assets frontend

```bash
npm install
npm run development
```

Para gerar os assets de produção:

```bash
npm run production
```

Os scripts `scripts/deploy-server.*` ainda fazem referência a `npm run build:prod`, que não existe no `package.json`. Até que sejam corrigidos, use `npm run production` diretamente.

## Editor e uploads

CKEditor e CKFinder estão incorporados ao projeto:

- `public/ckeditor`;
- `public/ckfinder`;
- uploads em `public/ckfinder/userfiles`.

O processo do servidor web precisa de permissão de leitura e escrita em `public/ckfinder/userfiles`. Esse diretório contém dados persistentes e deve fazer parte do backup, mas não deve ser substituído inadvertidamente durante o deploy.

## Exportação para PDF

O mecanismo recomendado é Playwright:

```env
PDF_ENGINE=playwright
PDF_PLAYWRIGHT_NODE_BINARY=node
PDF_PLAYWRIGHT_SCRIPT=/caminho/do/projeto/scripts/pdf-renderer/render-peticao.js
PDF_PLAYWRIGHT_BROWSER_BINARY=
```

Instale o renderer:

```bash
cd scripts/pdf-renderer
npm ci
npx playwright install chromium
```

Em servidores com Chrome/Chromium já instalado, `PDF_PLAYWRIGHT_BROWSER_BINARY` pode apontar para o executável. A resposta exportada deve ter `Content-Type: application/pdf` e iniciar diretamente com `%PDF`; bytes anteriores podem fazer sistemas de tribunais classificarem o documento como `text/plain`.

Existe fallback para HTML2PDF, porém ele é transitório e pode depender de uma biblioteca fora da raiz atual. Produção deve priorizar Playwright.

## Importação de Word

Defina o executável do LibreOffice:

```env
WORD_IMPORT_BINARY="C:\Program Files\LibreOffice\program\soffice.exe"
WORD_IMPORT_TIMEOUT=60
```

Em Linux, use o caminho correspondente ao `soffice` instalado.

## Integrações opcionais

### OpenAI

```env
OPENAI_ENABLED=true
OPENAI_API_KEY=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=
OPENAI_TIMEOUT=60
```

Nunca versione uma chave real no repositório.

### LanguageTool

```env
LANGUAGETOOL_ENABLED=false
LANGUAGETOOL_BASE_URL=http://127.0.0.1:8081
LANGUAGETOOL_LANGUAGE=pt-BR
```

### Sistemas externos

Os perfis de integração são administrados pela aplicação e podem representar sistemas jurídicos, bases processuais ou outras fontes de dados. As credenciais e os parâmetros de conexão devem ser mantidos fora do código e configurados de acordo com o conector utilizado.

## Testes

Execute:

```bash
php vendor/bin/phpunit
```

Ou, no Windows com o PHP do Laragon:

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" vendor/bin/phpunit
```

Há uma proteção deliberada em `tests/TestCase.php`: os testes só executam quando `APP_ENV=testing` e o nome do banco termina em `_test`. O padrão do `phpunit.xml` é:

```text
peticaofacil_laravel_test
```

Se houver cache de configuração do ambiente local ou de produção, limpe-o antes dos testes:

```bash
php artisan config:clear
php artisan route:clear
```

Nunca aponte o PHPUnit para uma base operacional.

## Deploy em produção

Antes do deploy, faça backup do banco e de `public/ckfinder/userfiles`.

Fluxo recomendado:

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run production
cd scripts/pdf-renderer && npm ci && cd ../..
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan up
```

Depois do deploy, valide:

- login e primeiro acesso;
- abertura e edição de um modelo;
- montagem e salvamento de uma petição;
- upload de imagem;
- exportação DOCX;
- exportação PDF e assinatura inicial `%PDF`;
- integração com sistemas externos, quando usada;
- revisão LanguageTool/IA, quando habilitada.

Não use em produção:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
```

Esses comandos podem apagar ou reconstruir tabelas.

## Backup e restauração

O backup mínimo deve incluir:

1. dump completo do MySQL;
2. `public/ckfinder/userfiles`;
3. `.env` armazenado de forma segura;
4. registro da versão/commit implantado.

Teste periodicamente a restauração em uma base separada. As tabelas `*_archive_*` ainda existentes são uma salvaguarda histórica e não devem ser removidas antes de um backup final validado.

## Segurança e pendências conhecidas

- senhas históricas ainda usam MD5; a migração gradual para bcrypt é a próxima prioridade de segurança;
- o banco permanece em `latin1`; a migração para `utf8mb4` deve ser feita separadamente;
- dependências PHP e frontend estão defasadas e precisam de atualização planejada;
- o fallback HTML2PDF deve ser removido após confirmar a estabilidade do Playwright;

## Estrutura útil

```text
app/                    aplicação Laravel
database/migrations/    evolução do esquema
public/ckeditor/         editor rico local
public/ckfinder/         gerenciador de arquivos local
resources/views/         telas Blade
scripts/pdf-renderer/    renderer PDF com Playwright
tests/                   testes unitários e funcionais
```

## Licença e acesso

Este é um sistema interno. Defina com a organização as regras de acesso, distribuição, retenção de documentos jurídicos e tratamento de dados pessoais antes de disponibilizar código ou bases de dados a terceiros.
