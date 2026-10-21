# Checklist de deploy — branch `peticaofacil_ia`

Cobre os commits de 05/10 a 20/10/2026 (achados 1–15, salvo o 4; motor dos
formulários Safra e Volkswagen; correção do obrigatório). Detalhe de cada mudança em `docs/HANDOFF-peticaofacil-ia.md`.

A produção recebe por `git pull`. Não há mudança em `composer.json`,
`package.json` nem no renderizador de PDF: não é preciso `composer install`,
`npm run production` nem `npm ci` em `scripts/pdf-renderer` para esta entrega
(rode-os só se o servidor estiver atrasado em relação a entregas anteriores).

## 1. Antes

- [ ] Backup completo do MySQL (`peticaofacil_laravel`).
- [ ] Backup de `public/ckfinder/userfiles` (o pull altera `public/ckfinder/config.php`,
      mas não pode tocar nos arquivos enviados pelos usuários).
- [ ] Anotar o commit atual do servidor (`git rev-parse HEAD`) para eventual volta.
- [ ] Conferir que o `.env` de produção tem `APP_KEY` definido. **Ele não pode mudar
      depois do deploy**: a senha do NEO passa a ser guardada cifrada com essa chave
      (migration `2026_10_05_000100`) e o acesso ao CKFinder usa um token assinado
      com ela. Trocar o `APP_KEY` = senha do NEO ilegível (recadastrar na tela).

## 2. `.env` de produção

Variáveis novas (todas opcionais; ver `.env.example`):

| Variável | Para quê | Produção |
| --- | --- | --- |
| `APP_DEBUG_IPS` | IPs que podem ver a tela de depuração | deixar vazio |
| `LEGACY_DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` | leitura do banco antigo pelos comandos `peticao:backfill-*` | **não é preciso em produção** (a migration usa o arquivo do repositório) |
| `LEGACY_DB_CHARSET/COLLATION` | charset do banco antigo | padrão latin1 |
| `LANGUAGETOOL_CONNECT_TIMEOUT` | tempo para desistir do LanguageTool | padrão 5 |

- [ ] Confirmar `APP_ENV=production` e `APP_DEBUG=false`.
- [ ] Se o LanguageTool não estiver instalado no servidor: `LANGUAGETOOL_ENABLED=false`.

## 3. Publicação

```bash
php artisan down
git pull
php artisan peticao:backfill-obrigatorio --snapshot   # relatório do item 4 (não grava)
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan up
```

Migrations desta entrega (todas podem rodar mais de uma vez):

1. `2026_10_05_000100_encrypt_sql_server_profile_passwords` — amplia a coluna e cifra
   as senhas dos servidores SQL.
2. `2026_10_05_000200_assign_link_code_to_sql_server_profiles` — dá código de vínculo
   aos servidores criados pela tela nova.
3. `2026_10_06_000100_clean_undefined_option_returns` — troca o texto "undefined"/"null"
   das opções de SELECT por vazio.
4. `2026_10_20_000100_restore_legacy_campo_obrigatorio_e_mascaras` — corrige o
   "obrigatório" dos campos, que veio **invertido** da migração do legado, e preenche
   máscaras vazias. Lê `database/data/legacy-campos.json` (vai no `git pull`): **não
   precisa do banco antigo em produção**. Ver item 4.

## 4. Obrigatório e máscaras dos campos — **importante**

A migração do legado gravou o "obrigatório" invertido (no legado `input_req = 2` é
Sim e `1` é Não): a montagem marca e cobra os campos errados, e o assistente pede os
campos errados. A migration 4 corrige isso sozinha no `migrate --force`.

Por que é seguro:
- só altera duas colunas de `peticao_modelo_campos` (`obrigatorio` e `comportamento`
  vazio); não apaga nem cria nada;
- localiza cada campo pelo código do legado (`legacy_input_id`), não pelo id do banco:
  vale para a produção mesmo ela sendo mais nova que a cópia local;
- campos criados no sistema novo (sem código do legado) não são tocados;
- campos editados na tela depois da migração (`updated_at > created_at`) mantêm o
  obrigatório escolhido;
- tudo numa transação; rodar de novo não muda nada.

Conferência (o `--snapshot` lê o mesmo arquivo da migration, sem o banco antigo):

```bash
# depois do git pull e ANTES do migrate: relatório do que vai mudar (não grava)
php artisan peticao:backfill-obrigatorio --snapshot
php artisan migrate --force
# depois: deve mostrar a_corrigir = 0
php artisan peticao:backfill-obrigatorio --snapshot
```

Esperado: algo perto de 2.744 campos a corrigir (o número local); diferença vem de
campos editados na tela em produção, que aparecem em `editados_mantidos`.
Se algo der errado, a volta é o backup do item 1 (a migration não tem `down`:
desfazer seria inverter de novo).

## 5. Conferência depois

- [ ] Login e primeiro acesso (troca de senha temporária).
- [ ] Admin → Servidores: abrir o NEO, **a senha não aparece na tela**.
- [ ] Montar uma peça informando um código de processo: os dados vêm do NEO
      (prova que a senha cifrada é lida corretamente).
- [ ] Montagem de um modelo Safra com lista dependente (ex.: INICIAL DE BUSCA:
      escolher ESTADO e ver só os depositários daquele estado) e com máscaras
      (data, valor, CPF/CNPJ).
- [ ] Opções de SELECT não mostram mais "undefined".
- [ ] Asteriscos de obrigatório nos campos certos (comparar um modelo com o legado).
- [ ] Safra INICIAL DE BUSCA: "COM SEGREDO DE JUSTIÇA? = SIM" preenche o título e o
      parágrafo; "LOCAL DA NOTIFICAÇÃO = PESQUISA" mostra o campo seguinte.
- [ ] Volkswagen MINUTA: TIPO DE PESSOA troca o documento entre CPF e CNPJ.
- [ ] Banner "não ajuizar" e resumo do TEXTAREA na montagem.
- [ ] Salvar a peça, reabrir, comparar versões.
- [ ] Usuário comum não abre peça salva de outro usuário (deve dar 403).
- [ ] Enviar imagem pelo editor (CKFinder) logado; deslogado, o CKFinder deve recusar.
- [ ] Importar um Word na peça salva.
- [ ] Exportar DOCX e PDF; o PDF começa com `%PDF`.
- [ ] Revisão LanguageTool (se habilitado) — desligado, a tela não deve travar.
- [ ] Modelo "Petição avulsa" não aparece na lista de modelos.
- [ ] Erro proposital (URL inexistente) não mostra a tela de depuração.

## 6. Se der errado

```bash
php artisan down
git checkout <commit anotado no passo 1>
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan up
```

As migrations 2 e 3 não precisam ser desfeitas (o código antigo ignora as mudanças).
A 1 deixa a senha do NEO cifrada, que o código antigo **não** sabe ler: antes de voltar
o código, rode `php artisan migrate:rollback --step=3` (decifra a senha) — ou restaure
o backup do banco.

## Depois que estabilizar

- Excluir as 13 tabelas `*_archive_20260727` / `*_archive_20260818` (uma delas guarda a
  senha antiga do NEO em texto puro). Fazer backup antes.
