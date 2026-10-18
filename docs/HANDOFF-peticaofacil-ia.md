# Handoff — branch peticaofacil_ia (atualizado 17/10/2026)

Histórico das correções feitas na branch `peticaofacil_ia`, para quem continuar
(ex.: Claude Code). Convenção de commit: prefixo `peticafacil_ia - `.

## Commits (local, SEM push)
05/10: `587d106c`(1) `49f364df`(2) `736f6560`(3) `bbc6dcb3`+`fecf9a28`(5)
`71b66230`(7/8) `dcdfa026`(9) `258d75fe`(10) `77463fac`(Stone v2)
`b0711742`(vínculo inativo) `62952513`(11) `0067f1c3`(12) `7d8848e3`(13).
06/10: `d72ff307`(undefined + migration 2026_10_06_000100) `7c2c80b0`(montagem:
resumo TEXTAREA + banner "não ajuizar") `906d7b84`(achado 14 — avulsa oculta)
`507d1455`(achado 15 LanguageTool) `dcad0d49`(**motor Safra: máscaras backfill +
listas dependentes**) `0f75160e`(correção de 2 testes desatualizados).
**Suíte: 223 testes, 1252 asserções, OK.**

## Achados (segurança/qualidade) — todos commitados salvo o 4
1. Chave `}` sobrando quebrava o JS da montagem (`assemble.blade.php`).
2. CKFinder autenticava sempre → token HMAC (`FileManagerToken`, cookie `pf_filemanager`).
3. Acesso às peças salvas: autor + GER do setor + ADM (`PeticaoPolicy`, `scopeVisibleTo`).
4. **ADIADO** — alcance do nível GER na administração (decisão do Fabio).
5. Senha do NEO cifrada (`Crypt`) + consulta parametrizada (`SqlServerLookupService`).
   Migration `2026_10_05_000100` cifra a coluna (depende do APP_KEY).
6. Export/import de modelo — tratado em 7/8.
7/8. Pacotes de modelo portáveis v2 (`PeticaoModeloPackageService`, `@campoN@`→
   `{{field:chave}}`); servidor SQL novo vinculável.
9. Ignition/tela de debug restrita à máquina local (`DebugAccess`).
10. Servidor SQL novo vinculável a modelo (`legacy_config_id` via `nextLinkCode`).
    Migration `2026_10_05_000200`.
11. Campos obrigatórios: avisa e deixa gerar (`PeticaoRequiredFieldsService`).
12. Importação de Word só lê imagens dentro da pasta da conversão (`WordImportService::resolveImagePath`).
13. HTML da petição saneado ao salvar/exportar (`HtmlSanitizer`).
14. Modelo de sistema "Petição avulsa" fora das listagens (`PeticaoModelo::withoutSystem/is_system`).
15. LanguageTool ligado sem serviço → robustez (fail-fast, flag `available`,
    auto-revisão só se habilitado). Sem migration.

## Formulários Safra (commit dcad0d49) — foco do último trabalho
Pedido: recuperar funcionalidades dinâmicas dos formulários que não migraram;
piloto nos 10 modelos Safra (ATIVAS) de menor ID legado. Cliente SAFRA (ATIVAS)
= `cliente_id=1` no admin novo.

### Mecanismo legado
- Máscaras: `tp_inputs_tb.input_alt` + `jquery.meio.mask` (`setMask()`, atributo `alt`).
- Listas: `tp_grupo_tb`/`tp_lista_tb` (return_1..6). `input_db` =
  `tabela _|_ col_exibida _|_ col_retorno _|_ id_grupo=N _|_ hori/vert _|_ campo_dependente`.
  `inc/ajax_horiz.php`: lista **hori** filtra por `nome_lista = valor(campo_dependente)`
  e insere `concat(return_2..return_6)`.

### O que foi feito
1. **Backfill de máscaras:** conexão `legacy_mysql`→`peticaofacil_old`
   (`config/database.php`, env `LEGACY_DB_*`); `PeticaoModeloCampo::mapLegacyBehavior()`;
   comando `peticao:backfill-comportamento` (le `tp_inputs_tb.input_alt`, casa por
   `legacy_input_id`, preenche `comportamento` vazio). Teste `CampoComportamentoMapTest`.
   **Relatório:** 519 já OK, 0 divergentes, só 1 faltando (processo) → **aplicado**.
   Ou seja, a migração de máscaras era praticamente fiel.
2. **Listas dependentes:** `PeticaoModeloCampo` (layout + dependency_field_id;
   hori = rótulo return_2 / valor concat return_2..6 / chave nome_lista; vert = valor
   é o rótulo) + `assemble.blade.php` (atributos data-* + módulo JS que filtra a lista
   pelo valor do campo dependente, começa vazia, repopula e reseta).
3. Correção de 2 testes com asserções desatualizadas da página de editar modelo
   (`AdminNormalizedModeloUpdateTest`, `AdminModeloMirrorReadTest`) → commit `0f75160e`.

### Verificação do piloto (ao vivo, 10 modelos) — TUDO OK
| legado→# | campos | máscaras | listas |
|---|---|---|---|
| 86→18 INICIAL DE BUSCA | 73 | date/decimal/integer | 2 dependentes (ESTADO) |
| 118→44 INICIAL DE REINTEGRAÇÃO | 42 | date/decimal/integer | 1 dependente (ESTADO) |
| 158→75 CONVERSÃO ENTREGA AMIGÁVEL | 15 | cpf/cnpj/date | — |
| 159→76 MINUTA DE ACORDO | 22 | cpf/cnpj/date/decimal | 1 vert (escolha) |
| 160→77 CONSOLIDAÇÃO DA POSSE | 12 | date | 1 vert → preenche OAB |
| 161→78 DESENTRANHAMENTO | 9 | date | — |
| 162→79 ACORDO PARCELADO | 22 | cpf/cnpj/date/decimal/integer | — |
| 166→82 ACORDO QUITAÇÃO À VISTA | 23 | cpf/cnpj/date/decimal | — |
| 167→83 ACORDO C/ SALDO | 14 | cpf/cnpj/date | 1 vert → preenche OAB |
| 173→84 ACORDO ALVARÁ E BOLETO | 29 | cpf/cnpj/date/decimal/integer | — |

Listas dependentes (hori): ESTADO=CE → só os depositários do CE; valor inserido =
cláusula concatenada (nome, CPF, telefone). Vert: valor=rótulo; as de preenchimento
(77/83) preenchem o alvo (OAB/UF) via fc_ajax_comp. Sem lacunas nos 10.

## Pendências / próximos passos
- **Push** da branch e **deploy** (`migrate --force` onde há migration: 2026_10_05_000100,
  2026_10_05_000200, 2026_10_06_000100). Produção recebe por `git pull` (sem acesso ao servidor).
- Em produção, se `peticaofacil_old` não estiver lá, rodar o backfill com `--sql=arquivo`
  e aplicar o .sql.
- Opcional: regra de auto-máscara (CPF/CNPJ/CEP/telefone onde o legado nunca mascarava).
- Varredura dos formulários dos outros clientes (o motor é genérico; o backfill já
  cobriu máscaras nos 238 modelos).
- Excluir tabelas `*_archive_20260727` / `*_archive_20260818` (13) quando estável
  (incluem senha antiga do NEO em texto puro).
- Achado 4 (GER na administração).

## Notas de ambiente
- PHP 7.2.34 alvo (não usar sintaxe 8+). PHPUnit no Laragon. Banco de teste `*_test`.
- Charset latin1 no MySQL; `legacy_mysql` usa latin1.
