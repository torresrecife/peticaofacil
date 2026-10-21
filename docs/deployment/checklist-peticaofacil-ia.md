# Checklist de deploy — branch `peticaofacil_ia`

Cobre os commits de 05/10 a 06/10/2026 (achados 1–15, salvo o 4; motor dos
formulários Safra). Detalhe de cada mudança em `docs/HANDOFF-peticaofacil-ia.md`.

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
| `LEGACY_DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` | leitura do banco antigo pelo backfill de máscaras | só se o `peticaofacil_old` existir no servidor; vazio reaproveita `DB_*` |
| `LEGACY_DB_CHARSET/COLLATION` | charset do banco antigo | padrão latin1 |
| `LANGUAGETOOL_CONNECT_TIMEOUT` | tempo para desistir do LanguageTool | padrão 5 |

- [ ] Confirmar `APP_ENV=production` e `APP_DEBUG=false`.
- [ ] Se o LanguageTool não estiver instalado no servidor: `LANGUAGETOOL_ENABLED=false`.

## 3. Publicação

```bash
php artisan down
git pull
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

## 4. Máscaras dos campos (backfill)

O backfill já foi aplicado no banco local. Em produção, escolha um caminho:

- **O servidor enxerga o `peticaofacil_old`:**
  ```bash
  php artisan peticao:backfill-comportamento          # relatório, não grava
  php artisan peticao:backfill-comportamento --apply  # grava
  ```
- **Não enxerga:** gere o SQL numa máquina que enxergue os dois bancos e aplique-o
  no MySQL de produção:
  ```bash
  php artisan peticao:backfill-comportamento --sql=backfill-mascaras.sql
  ```
  Atenção: os UPDATEs casam pelo `legacy_input_id`, então o arquivo deve ser gerado
  contra um banco novo equivalente ao de produção.

Esperado (pelo relatório local): só 1 campo a preencher; os demais já estavam certos.

## 4b. Campo "obrigatório" (backfill) — **importante**

A migração gravou o "obrigatório" **invertido** (no legado `input_req = 2` é Sim e
`1` é Não). Sem esta correção, a montagem marca com asterisco e cobra os campos
errados, e o assistente pede os campos errados. Mesmos caminhos do item 4:

```bash
php artisan peticao:backfill-obrigatorio          # relatório, não grava
php artisan peticao:backfill-obrigatorio --apply  # grava
# ou, sem acesso ao banco antigo:
php artisan peticao:backfill-obrigatorio --sql=backfill-obrigatorio.sql
```

Esperado (pelo relatório local): ~2.744 campos a corrigir. Campos editados na tela
depois da migração são mantidos (relatório: `editados_mantidos`); `--overwrite`
corrige também esses. Rode **uma vez só**: o comando compara com o legado, então
repetir não desfaz nada, mas `--overwrite` sobrepõe escolhas feitas na tela.

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
