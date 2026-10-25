# Checklist de deploy — branch `peticaofacil_ia`

Cobre os commits de 05/10 a 24/10/2026 (achados 1–15, salvo o 4; motor dos
formulários Safra, Volkswagen, sem cliente e Itaú; correção do obrigatório; montagem do
texto com as regras do legado; painel de dinâmicas na tela de edição; importação com
IA propondo dinâmicas). Detalhe em `docs/HANDOFF-peticaofacil-ia.md` e
`docs/analise-dinamicas-formularios.md`.

**Avise os usuários:** as peças novas passam a sair como saíam no legado. Por exemplo,
"Ação de **Busca e Apreensão**" em vez de "Ação de **BUSCA E APREENSÃO EM ALIENAÇÃO
FIDUCIÁRIA**", nomes em maiúsculas onde o marcador é `@CAMPO@`, e a tabela de parcelas
só com as parcelas escolhidas. Os asteriscos de obrigatório mudam de campo (estavam
invertidos). As peças já salvas não mudam.

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
5. `2026_10_25_000100_apply_document_masks_by_label` — máscara de CPF, CNPJ, CPF/CNPJ
   e CEP pelo rótulo dos campos de texto (33 campos no local). Corrige campos sem
   máscara e campos "CPF/CNPJ" com máscara só de CPF, que cortava o CNPJ vindo do NEO
   em 11 dígitos. Só altera a máscara ausente ou incompatível.

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

**Tipografia (25/10)**
- [ ] Abrir uma peça salva antiga no editor: letra em tamanho normal (10/11pt) e
      parágrafos compactos como no legado; exportar PDF e Word e comparar com o legado.
- [ ] Cabeçalho antigo (logotipo + contato) sem quebra de linha no PDF.

**Montagem do texto (fase 1)**
- [ ] EXPEDIÇÃO DE NOVO MANDADO (o mais usado), com um código de processo: a prévia
      traz "COMARCA DE <cidade> - <UF>" em maiúsculas e "Ação de Busca e Apreensão…"
      com iniciais maiúsculas.
- [ ] MINUTA Volkswagen: quantidade de parcelas = 3, preencher a 1ª: a tabela da prévia
      tem só 3 linhas, sem `@campo…_pre@` nem tags à mostra.
- [ ] AÇÃO DE EXECUÇÃO (#34): PESSOA = FÍSICA sai "pessoa FÍSICA" (antes saía "1").
- [ ] AÇÃO DE EXECUÇÃO (#34) com um código de processo: CPF/CNPJ e CEP já vêm
      formatados; PESSOA = FÍSICA limita o documento a CPF, JURÍDICA a CNPJ.
- [ ] REQUERIMENTO: com o código do processo, a QUALIFICAÇÃO vem preenchida ("pessoa
      física, inscrita no CPF sob o nº …").

**Tela de edição (fase 2)**
- [ ] Admin → Modelos → INICIAL DE BUSCA (Safra) → campo "COM SEGREDO DE JUSTIÇA?": o
      painel "Dinâmica do campo" mostra "A opção escolhida preenche outros campos" com
      os dois alvos. Salvar o campo sem mudar nada e conferir na montagem que SIM ainda
      preenche o título e o parágrafo (as opções e os retornos 2–5 não se perdem).
- [ ] Inserir um marcador pelo ajudante acima dos parágrafos (num modelo de teste).

**Importação com IA (fase 3)** — se o `OPENAI_API_KEY` estiver configurado
- [ ] Criar com IA a partir de um Word curto: a revisão mostra "No texto", "Extenso",
      opções com retorno e as dinâmicas propostas. Criar, conferir e inativar o modelo de
      teste.

## 6. Se der errado

```bash
php artisan down
git checkout <commit anotado no passo 1>
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan up
```

As migrations 2 a 5 não precisam ser desfeitas (o código antigo ignora as mudanças;
a 4 e a 5 só corrigem obrigatório e máscaras, que continuam certos). A 1 deixa a senha do NEO cifrada, que
o código antigo **não** sabe ler: antes de voltar o código, rode
`php artisan migrate:rollback --step=5` (desfaz as cinco desta entrega; só a 1 tem
efeito ao desfazer: decifra a senha) — ou restaure o backup do banco.

## Depois que estabilizar

- Excluir as 13 tabelas `*_archive_20260727` / `*_archive_20260818` (uma delas guarda a
  senha antiga do NEO em texto puro). Fazer backup antes.
