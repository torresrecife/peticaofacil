# Petição Fácil

Internal system for creating, editing, reviewing, versioning, and exporting legal petitions. The application manages reusable templates, paragraphs, dynamic fields, option lists, clients, departments, and integrations with external data sources.

The project is a standalone Laravel application with document editing, legal content administration, and optional integrations.

## Features

- authentication, first-access flow, and user access levels;
- dashboard with favorites and daily production summaries;
- user, department, and client management;
- template, paragraph, and dynamic field management;
- option lists and field return values;
- guided petition assembly;
- free-form petitions;
- assembly assistant with optional OpenAI integration;
- optional process lookup in legal systems or other external sources;
- local rich-text editor powered by CKEditor and CKFinder;
- petition persistence and version history;
- version comparison and restoration;
- LanguageTool review and optional AI review;
- Word document import through LibreOffice;
- DOCX and PDF export;
- operational statistics.

## Current architecture

- PHP `^7.2.5` or `^8.0`;
- Laravel `6.20`;
- MySQL;
- configurable connectors for external systems;
- PHPWord for DOCX generation;
- Playwright/Chromium for PDF generation;
- CKEditor and CKFinder served from `public/`;
- Laravel Mix 5 for compiled assets.

The main database tables are:

- `users`;
- `setores` and `clientes`;
- `peticao_modelos`;
- `peticao_modelo_paragrafos`;
- `peticao_modelo_campos`;
- `peticao_modelo_campo_opcoes`;
- `lista_grupos` and `lista_itens`;
- `peticoes` and `peticao_versoes`;
- `user_model_favorites`;
- `user_languagetool_preferences`.

## Requirements

- PHP with the extensions required by Laravel, MySQL, DOM/XML, cURL, Mbstring, Zip, and GD;
- Composer;
- MySQL;
- Node.js and npm;
- Chromium, Chrome, or Edge for PDF generation;
- LibreOffice when Word import is enabled;
- access to external systems, OpenAI, and LanguageTool only when those integrations are enabled.

The Windows development environment uses Laragon and PHP 7.2.34. For new installations, prefer a supported PHP version and verify Laravel 6 compatibility beforehand.

## Local installation

Clone the repository and install the PHP dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

On PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

At a minimum, configure `APP_URL` and the `DB_*` variables. The database currently uses:

```env
DB_CHARSET=latin1
DB_COLLATION=latin1_swedish_ci
```

Do not change the production character set directly. A future conversion to `utf8mb4` requires a migration and an audit of the existing text data.

Then run:

```bash
php artisan migrate
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Frontend assets

```bash
npm install
npm run development
```

To build production assets:

```bash
npm run production
```

The `scripts/deploy-server.*` scripts still refer to `npm run build:prod`, which is not defined in `package.json`. Until those scripts are corrected, run `npm run production` directly.

## Editor and uploads

CKEditor and CKFinder are bundled with the project:

- `public/ckeditor`;
- `public/ckfinder`;
- uploads in `public/ckfinder/userfiles`.

The web server process requires read and write access to `public/ckfinder/userfiles`. This directory contains persistent data and must be included in backups, but it must not be overwritten inadvertently during deployment.

## PDF export

Playwright is the recommended engine:

```env
PDF_ENGINE=playwright
PDF_PLAYWRIGHT_NODE_BINARY=node
PDF_PLAYWRIGHT_SCRIPT=/path/to/project/scripts/pdf-renderer/render-peticao.js
PDF_PLAYWRIGHT_BROWSER_BINARY=
```

Install the renderer:

```bash
cd scripts/pdf-renderer
npm ci
npx playwright install chromium
```

On servers where Chrome or Chromium is already installed, `PDF_PLAYWRIGHT_BROWSER_BINARY` may point to its executable. The exported response must have `Content-Type: application/pdf` and begin directly with `%PDF`; leading bytes may cause court systems to classify the document as `text/plain`.

An HTML2PDF fallback is available, but it is transitional and may depend on a library outside the current project root. Production should prioritize Playwright.

## Word import

Configure the LibreOffice executable:

```env
WORD_IMPORT_BINARY="C:\Program Files\LibreOffice\program\soffice.exe"
WORD_IMPORT_TIMEOUT=60
```

On Linux, use the path of the installed `soffice` executable.

## Optional integrations

### OpenAI

```env
OPENAI_ENABLED=true
OPENAI_API_KEY=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=
OPENAI_TIMEOUT=60
```

Never commit a real API key to the repository.

### LanguageTool

```env
LANGUAGETOOL_ENABLED=false
LANGUAGETOOL_BASE_URL=http://127.0.0.1:8081
LANGUAGETOOL_LANGUAGE=pt-BR
```

### External systems

Integration profiles are managed by the application and may represent legal systems, procedural databases, or other data sources. Credentials and connection parameters must remain outside the source code and be configured according to the connector being used.

## Tests

Run:

```bash
php vendor/bin/phpunit
```

On Windows with the Laragon PHP executable:

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" vendor/bin/phpunit
```

`tests/TestCase.php` contains a deliberate safety guard: tests run only when `APP_ENV=testing` and the database name ends in `_test`. The default database in `phpunit.xml` is:

```text
peticaofacil_laravel_test
```

If configuration from the local or production environment has been cached, clear it before running tests:

```bash
php artisan config:clear
php artisan route:clear
```

Never point PHPUnit at an operational database.

## Production deployment

Before deployment, back up the database and `public/ckfinder/userfiles`.

Recommended workflow:

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

After deployment, verify:

- login and first-access flow;
- template opening and editing;
- petition assembly and persistence;
- image uploads;
- DOCX export;
- PDF export and the initial `%PDF` signature;
- external system integration, when enabled;
- LanguageTool and AI review, when enabled.

Do not run in production:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
```

These commands may delete or rebuild tables.

## Backup and restoration

The minimum backup set must include:

1. a complete MySQL dump;
2. `public/ckfinder/userfiles`;
3. the `.env` file stored securely;
4. the deployed version or commit identifier.

Periodically test restoration into a separate database.

## Security and known technical work

- passwords are stored with bcrypt; accounts that still contain an MD5 hash are upgraded automatically after their next successful login;
- the database remains on `latin1`; migration to `utf8mb4` must be handled separately;
- PHP and frontend dependencies require a planned upgrade;
- the HTML2PDF fallback should be removed after Playwright stability is confirmed.

## Useful project structure

```text
app/                    Laravel application
database/migrations/    schema evolution
public/ckeditor/         local rich-text editor
public/ckfinder/         local file manager
resources/views/         Blade views
scripts/pdf-renderer/    Playwright PDF renderer
tests/                   unit and feature tests
```

## License and access

This is an internal system. Before sharing the source code or databases with third parties, define the organization's access, distribution, legal-document retention, and personal-data handling policies.
