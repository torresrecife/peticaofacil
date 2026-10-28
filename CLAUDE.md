# Petição Fácil — contexto para o Claude Code

Sistema de elaboração de petições jurídicas (recuperação de crédito de
financiamento de veículos). Migrado de um sistema antigo em PHP puro para este
projeto Laravel. Este arquivo é lido automaticamente pelo Claude Code.

## Stack e ambiente
- Laravel 6.20, **PHP 7.2.34** (alvo real; não usar sintaxe 8+), MySQL (latin1),
  Blade com JS embutido, CKEditor 4 + CKFinder em `public/`. Roda em Laragon/Windows.
- App novo: `http://bvaa.test/peticaofacil` · banco `peticaofacil_laravel`.
- Sistema antigo (referência): `http://peticaofacil.test/index.php` · banco
  **`peticaofacil_old`** (mesmo MySQL). Código do legado fora deste repo
  (`C:\laragon\www\peticaofacil`).
- NEO (SQL Server, dados do processo): perfil em `sql_server_profiles`
  (ID 1, legacy 54), banco `Neo_Juridico_replica`. Código de processo p/ teste: `32216005`.

## Git
- Remoto `torresrecife/peticaofacil`, branch de trabalho **`peticaofacil_ia`**
  (vários commits locais **SEM push** — o push é decisão do Fabio).
- **Convenção de mensagem de commit: prefixo `peticafacil_ia - `** (sim, "peticafacil").

## Como rodar (no Laragon, terminal normal)
- Testes: `php vendor/bin/phpunit` (PHPUnit 8.5). Banco de teste deve terminar em
  `_test` e `APP_ENV=testing` (há guarda em `tests/TestCase.php`).
  Estado atual da suíte: **291 testes, OK**.
- Migrations: `php artisan migrate` (produção: `--force`).
- Backfill de máscaras: `php artisan peticao:backfill-comportamento`
  (`--dry-run` é o padrão/relatório; `--apply` grava; `--modelo=`, `--overwrite`,
  `--sql=arquivo`). **Já aplicado localmente** (1 campo).
- Backfill do obrigatório: `php artisan peticao:backfill-obrigatorio` (mesmas opções,
  mais `--overwrite` e `--snapshot`, que lê `database/data/legacy-campos.json`). **Já aplicado localmente** (2.744).
- Fluxo combinado: implementar → rodar PHPUnit → commitar com o prefixo.

## Mapa do motor dos formulários (montagem)
- View: `resources/views/peticao/assemble.blade.php` — JS vanilla que faz:
  máscaras (`applyFieldBehavior` lê `data-input-behavior`: date/decimal/integer/
  cpf/cnpj/cpf_cnpj/cep/fone/processo), revelar condicional (`structuredConditionalRules`),
  campos contados (bens/parcelas), editor rico do TEXTAREA (modal + textos
  predefinidos), datas por extenso, preenchimento dependente (`applyDependentSelect`
  via fc_ajax_comp / data-return-*), cópias jQuery (`copyFieldValue`), TIPO DE PESSOA
  (`applyPersonType`), QUALIFICAÇÃO (`mergeFields`), **listas dependentes** (`data-list-select`/
  `data-dependency-field`/`data-dependency-key`) e banner "não ajuizar"
  (`validateLegacyPetition`).
- Modelo de campo: `app/PeticaoModeloCampo.php`.
  - `comportamento` = máscara; `origem_alias` guarda o `input_db` do legado
    (`tabela _|_ col_exibida _|_ col_retorno _|_ id_grupo=N _|_ hori/vert _|_ campo_dependente`).
  - `getAssociatedListConfigAttribute` lê layout (hori/vert) + `dependency_field_id`.
  - `buildListSelectOptions`: **hori** → rótulo=return_2, valor=concat(return_2..6),
    chave=nome_lista; **vert** → valor=rótulo (o RETURN alimenta o campo alvo).
  - `mapLegacyBehavior()` (estático) = mapa `input_alt`→`comportamento`.
- Listas: tabelas `lista_grupos`/`lista_itens` (return_1..6). Conexão de leitura
  ao banco antigo: `legacy_mysql` em `config/database.php` (env `LEGACY_DB_*`).

## Estado atual (ver docs/HANDOFF-peticaofacil-ia.md para o detalhe)
- Achados de segurança/qualidade 1–15 corrigidos e commitados (4 adiado: alcance
  do GER na administração).
- **Formulários Safra (commit dcad0d49):** máscaras (backfill) + listas
  dependentes. Piloto dos 10 modelos Safra de menor ID legado verificado ao vivo,
  tudo OK.
- **Formulários Volkswagen (26) e modelo Stone (#222):** dados conferidos com o
  legado (fiéis); motor ganhou cópia de valor em jQuery, HIDDEN com id/evento e
  `alteraInput` (CPF/CNPJ). Detalhe no handoff.
- **Safra completa (47):** dois `fc_ajax_comp`/`mcampo` no mesmo evento e
  mostrar/ocultar em ternário jQuery. **"Obrigatório" vinha invertido da migração
  em todo o sistema** (legado: `input_req` 2 = Sim): `peticao:backfill-obrigatorio`,
  já aplicado localmente; em produção vai pela migration `2026_10_20_000100`
  (lê `database/data/legacy-campos.json`, sem o banco antigo).
- **Sem cliente (108):** 91 do legado fiéis; `cpfcnpj_mask` e
  `merge_fields` do #17 implementados (QUALIFICAÇÃO automática). **Itaú (29):** fiel, nada a corrigir. Os 17 modelos de teste da IA (#224–241)
  foram inativados no banco local.
- **Montagem do texto (fase 1, 24/10):** `PeticaoComposerService` segue o `parag.php`:
  `@CAMPO@`=MAIÚSCULAS, `@Campo@`=Iniciais, `@campo@`=como digitado; `_pre`/`_pos` só com
  valor; select do legado = texto da opção. Ver `docs/analise-dinamicas-formularios.md`.
- **Tela de edição (fase 2):** painel "Dinâmica do campo" gera os scripts do legado
  (`App\Support\CampoDinamica`: fromRequest/scripts/detect); opções atualizadas pelo
  rótulo (mantém legacy_dado_id e retornos 2–5).
- **Importação com IA (fase 3):** o agente devolve marcador/extenso/opções com retorno/
  dinâmica por campo; `createFromAi` grava como scripts (`applyAiDynamics`). Select
  criado no sistema novo traz o retorno (HTML do trecho opcional, saneado).
- **Tipografia (25/10):** `App\Support\PeticaoTipografia` (px→pt a partir de 10px, parágrafo
  sem margem, entrelinha 1.15) no editor, prévia, impressão, PDF e Word; CKEditor carregado
  por `partials/ckeditor.blade.php` (carimbo de versão contra cache).
- **Importação DOCX (25/10):** LibreOffice (caminho do perfil corrigido no Windows) +
  `App\Support\WordHtmlCleaner` (limpa o HTML, notas de rodapé no fim da peça); a IA só
  aponta os campos e o corpo é o HTML do Word.
- **Modelos VOLKSWAGEN – iniciais de busca (26/10):** 4 modelos (#253–#256 no local) a partir
  dos DOCX; já importados em produção (#224–#227) pela tela Exportar/Importar modelo. Correções
  junto: logotipo duplicado, listas no Word, pacote de exportação portável (marcadores/qualificação/
  tipo de pessoa), valor padrão em campo de texto.
- **Cabeçalho padrão dos modelos (27/10):** tabela `configuracoes` (`App\Configuracao`), tela
  Admin → Modelos → Cabeçalho padrão; vale para "Novo modelo" e importação com IA.
- **Botão "Reportar bug" (28/10):** widget do Kanban (`C:\Users\fabio\Projetos\kanban`, projeto à parte)
  incluído por `partials/bug-reporter.blade.php` no layout para quem está logado; liga com
  `BUG_REPORTER_SCRIPT` + `BUG_REPORTER_TOKEN` no `.env` (vazio = sem botão). Envia nome/login/nível do usuário.
- Teste de JS sem login: gerar o HTML da montagem como ADM pelo kernel do Laravel e
  abrir num Chromium headless (Playwright de `scripts/pdf-renderer`).

## Pendências
- **Push da branch `peticaofacil_ia`** e **deploy** (`migrate --force` onde houver
  migration). Produção recebe por `git pull`; sem acesso direto ao servidor.
- Auto-máscara feita (25/10): migration `2026_10_25_000100` (CPF/CNPJ/CEP pelo rótulo,
  `App\Support\CampoMascaraPorRotulo`) e PESSOA FÍSICA/JURÍDICA liga a máscara do
  documento mais próximo (`linkPersonTypeToDocument` na montagem). Telefone: nenhum campo.
- Publicar o Kanban no servidor de produção (exige PHP 8.3+, o Petição Fácil roda em 7.2) e então
  preencher `BUG_REPORTER_*` no `.env` de produção.
- Varredura dos formulários dos demais clientes (motor já é genérico).
- Excluir tabelas arquivadas `*_archive_*` (13) quando o sistema estiver estável.
- Achado 4 (o que o nível GER pode na administração) — decisão do Fabio.

## Observações
- Com o Claude Code você tem terminal direto: use `git`, `php artisan`, `phpunit`
  normalmente (não precisa dos truques de plumbing do git que a sessão anterior
  usava por limitação de ferramenta).
