# Dinâmicas dos formulários — legado × sistema novo

Análise de 24/10/2026. Fontes: código do legado (`C:\laragon\www\peticaofacil`:
`js/default.js`, `dados.php`, `parag.php`), banco `peticaofacil_old` (3.275 campos),
as 1.000 peças geradas no legado e migradas (`peticoes.legacy_peca_id`), que mostram
o texto que o legado realmente produzia, e testes no sistema novo (motor da montagem,
composição do texto e importação com IA).

## 1. Modelos mais usados (peças salvas)

| Peças | Modelo | Cliente | Campos com evento |
|---|---|---|---|
| 263 | #9 EXPEDIÇÃO DE NOVO MANDADO | — | 5 |
| 215 | #14 JUNTADA DE CUSTAS | — | 1 |
| 117 | #18 INICIAL DE BUSCA | Safra | 30 |
| 74 | #1 DESISTÊNCIA | — | 2 |
| 62 | #15 NOMEAÇÃO FIEL DEPOSITÁRIO | — | 3 |
| 54 | #17 REQUERIMENTO | — | 11 |
| 47 | #11 BACEN, INFOJUD E RENAJUD | — | 1 |
| 32 | #130 BAIXA DE REQUERIMENTO / #80 DILAÇÃO DE PRAZO | Volkswagen | 1 |
| 21 | #5 MINUTA (acordo com parcelas) | Volkswagen | 29 |

## 2. Catálogo das dinâmicas do legado

### 2.1 Nos campos (eventos ao entrar, carregar e sair)
Todas as 22 funções usadas nos campos ativos já rodam no motor novo
(`resources/views/peticao/assemble.blade.php`):

| Dinâmica | Função no legado | Usos | Situação |
|---|---|---|---|
| Opção do select preenche outro campo ou parágrafo (retorno da opção) | `fc_ajax_comp` | 268 | ok (inclui dois alvos no mesmo select) |
| Mostrar/ocultar e tornar obrigatório por SIM/NÃO | `mcampo` | 269 | ok |
| Mostrar/ocultar por valor (expressão jQuery) | ternário `$(this).val()==…` | 1 | ok |
| Quantidade de parcelas/bens mostra N campos | `nparcelas`, `nbens` | 7 | ok |
| Data por extenso, data atual, dia da semana | `data_extenso_out`, `data_atual`, `diasemana` | 460 | ok |
| Valor com extenso | `fc_newstring` | 178 | ok |
| Número com extenso e substantivo | `fc_numextenso` | 2 | ok |
| UF → nome do estado | `estado_ext` | 9 | ok |
| Copiar valor entre campos (também para ocultos) | `$("#campoA").val(…)` | 32 | ok |
| Repetir valor / somar meses na próxima parcela | `repeteValor`, `addDataCampo` | 68 | ok |
| Tipo de pessoa troca CPF/CNPJ | `alteraInput` | 3 | ok |
| Qualificação da parte | `merge_fields` | 1 | ok (nunca existiu no legado; implementado 24/10) |
| Máscara CPF/CNPJ | `cpfcnpj_mask` | 1 | ok (idem) |
| Bloqueio "não ajuizar" | `validate_peticao` | 17 | ok |
| Listas (vertical: escolher; horizontal: filtrada por outro campo) | `input_db` `tp_lista_tb` | 80+ | ok |
| Máscaras | `input_alt` | 614 | ok |

### 2.2 Na montagem do texto — **aqui estão as lacunas**
O legado transformava os valores ao montar o texto (`parag.php`). O sistema novo
(`PeticaoComposerService`) não faz nenhuma destas transformações:

1. **A forma do marcador define maiúsculas e minúsculas.** ❌ Não existe no novo.
   - `@CAMPO123@` → valor em MAIÚSCULAS
   - `@Campo123@` → Iniciais Maiúsculas, com "e", "de", "o", "no" e "nº" em minúsculas
     e "CPF" e "S.A" preservados (`upwords`)
   - `@campo123@` → como foi digitado
   - Nos modelos ativos: **1.594** `@CAMPO@` e **331** `@Campo@`, em mais de 140 modelos.
   - Exemplo real (#9, o mais usado): o legado gerava "Ação de **Busca e Apreensão**";
     o novo gera "Ação de **BUSCA E APREENSÃO EM ALIENAÇÃO FIDUCIÁRIA**".
2. **Prefixo/sufixo por marcador** (`@campo667_pre@` / `@campo667_pos@`). ❌ Quebrado.
   Usado nas tabelas de parcelas dos acordos (#5, #18, #39, #42, #44). Cada linha da
   tabela (`<tr>…`) fica no prefixo e no sufixo e só aparece quando a parcela existe:
   quando `nparcelas` esconde o campo, o legado apaga também o prefixo e o sufixo.
   No novo, a peça sai com os marcadores `@campo667_pre@` literais, com as tags como
   texto (`&lt;tr&gt;`) e com linhas para parcelas inexistentes.
3. **Prefixo/sufixo simples** (ex.: "R$ "): o novo sempre junta, mesmo com o campo
   vazio, e escapa o HTML. Precisa: só juntar quando há valor e aceitar HTML.
4. **Encurtamento fixo** vindo do NEO: "BUSCA E APREENSÃO EM ALIENAÇÃO FIDUCIÁRIA" →
   "BUSCA E APREENSÃO" (`dados.php`). ❌ Não existe no novo.
5. **Limpeza de vírgulas** de campos vazios: ", , ," → ", ," → ",". ❌ Não existe no novo.

## 3. Tela de edição de modelos (admin) — o que dá para configurar sem programar

| Dinâmica | Hoje na tela |
|---|---|
| Máscara | lista (data, valor, CPF, CNPJ, CPF/CNPJ, fone, CEP, número, processo) ✔ |
| Data por extenso / atual / dia da semana / valor por extenso | lista de "presets" ✔ |
| Mostrar/ocultar por valor | regra condicional estruturada ✔ |
| Lista associada que preenche outro campo | ✔ |
| Retorno da opção preenche outro campo/parágrafo | só escrevendo o script do legado à mão |
| Quantidade → mostra N campos (parcelas/bens) | só script |
| Copiar valor entre campos | só script |
| Tipo de pessoa → CPF/CNPJ; qualificação | só script |
| "Não ajuizar" | só script |
| Linhas de tabela por prefixo/sufixo | só marcador `_pre/_pos` à mão |
| Maiúsculas/Iniciais/como digitado | só mudando o marcador à mão no texto |

## 4. Importação de DOCX com IA (`PeticaoModeloAiBuilderService`)

O agente devolve, por campo: rótulo, tipo (TEXT/TEXTAREA/SELECT), obrigatório, coluna
do NEO, lista, máscara, prefixo/sufixo e opções (só o texto). Não sabe gerar:
- opção com **texto de retorno** (o parágrafo que entra quando se escolhe SIM, por
  exemplo); hoje o retorno é igual ao rótulo;
- **campos condicionais** (parágrafo opcional que só aparece com SIM);
- **campos repetidos por quantidade** (parcelas, bens, executados);
- **qualificação** (pessoa física/jurídica + CPF/CNPJ), **valor por extenso**,
  **data por extenso**, **maiúsculas/iniciais** pelo marcador;
- **"não ajuizar"**.

Bug: todo campo de data recebe `data_atual(this)` ao **sair**, o que troca a data
digitada pela data de hoje (`NormalizedTipoController::frontendEventsForBehavior`).
Data comum deveria ter só a máscara. `data_atual` serve só para "data da petição", e
ao carregar a página.

## 4b. Fase 1 — feita em 24/10/2026

`PeticaoComposerService` reescrito com as regras do `parag.php`:
- **Forma do marcador:** `@CAMPO@` em maiúsculas, `@Campo@` com iniciais
  maiúsculas e `@campo@` como digitado. A transformação respeita tags e entidades HTML.
  Diferenças em relação ao legado, que eram erros dele: nas iniciais maiúsculas,
  "da/de/do/dos/das/com/para/sob/a…" ficam minúsculas (o legado deixava "Dos", "Da"),
  a UF depois de "-" ou "/" fica em maiúsculas ("- PE"; o legado gerava "- Pe"), siglas
  como CPF, CNPJ, S.A, LTDA e ME ficam em maiúsculas, e acentos funcionam ("João").
  Quando o mesmo campo aparece no texto em duas formas (#5: `@CAMPO697@` e
  `@campo697@`), as duas são trocadas; o legado só trocava a primeira e deixava a
  outra literal na peça.
- **`@campoN_pre@` / `@campoN_pos@`** e prefixo/sufixo simples só entram quando o campo
  tem valor, sem escapar o HTML. A tabela de parcelas volta a ter uma linha por parcela.
- **Select vindo do legado:** o marcador traz o texto da opção escolhida, como no
  legado (o value era o `nome_dados`). Campo criado no sistema novo continua trazendo
  o retorno da opção (ex.: Stone). Corrige o #34 ("pessoa **1**" → "pessoa **FÍSICA**").
- **Limpeza de vírgulas** de campos vazios, como no legado.
- **Importador com IA:** data não ganha mais `data_atual` ao sair do campo. Também
  corrigido o erro 500 da criação sem cliente/servidor.
- **Não feito, a pedido:** o encurtamento "…EM ALIENAÇÃO FIDUCIÁRIA" → "BUSCA E APREENSÃO".

**Comparação legado × novo** nos 8 mais usados (#9, #18, #5, #14, #1, #15, #17, #11),
com os dados do NEO do processo 32216005 e as interações do formulário: o algoritmo do
`parag.php` (copiado, rodado offline) e o motor novo geram o mesmo texto. Sobram só o
cabeçalho institucional (que o novo inclui) e as correções acima. O `parag.php` não roda
no MySQL local (`ONLY_FULL_GROUP_BY` quebra o `SELECT * … GROUP BY`); é problema do
ambiente local, não da produção do legado.

Achado de dados (não corrigido): no #34, as opções de PESSOA têm retorno "1"/"2" e
esse retorno abastece o campo "inscrita no @campo1199@", que sai "inscrita no 1". O
legado fazia igual. O retorno deveria ser "CPF"/"CNPJ" (ou usar a qualificação
automática do #17).

## 4c. Fase 2 — feita em 24/10/2026 (tela de edição)

Painel **"Dinâmica do campo"** ao criar e editar campo (`partials/dinamica.blade.php`,
`App\Support\CampoDinamica`). Cada escolha gera o script que o motor já executa e
guarda a configuração em `eventos_frontend['dinamica']`:

| Dinâmica na tela | Script gerado |
|---|---|
| A opção escolhida preenche outros campos (até 3 alvos, Retorno 1–5, esconder com NÃO) | `fc_ajax_comp("tp_dados_tb",…)` + `mcampo` |
| Mostrar campos quando a resposta for SIM | `mcampo` |
| Mostrar campos quando o valor for… | regra condicional estruturada |
| A quantidade mostra campos (1 por item = bens, 2 = parcelas) | `nbens` / `nparcelas` |
| Copiar o valor para outros campos | `$("#campoN").val($(this).val())` |
| Próxima parcela (data +1 mês / mesmo valor) | `addDataCampo` / `repeteValor` |
| Tipo de pessoa troca CPF/CNPJ | `alteraInput` (com os códigos das opções FÍSICA/JURÍDICA) |
| Qualificação automática | `merge_fields("pessoa |P|D",…)` |
| "Não ajuizar" (NÃO, ou AUSENTE/MUDOU-SE/DESCONHECIDO) | `validate_peticao` + classe `new_required` |

- **Campos do legado:** a tela reconhece a dinâmica pelo script (119 campos), mas só
  quando o script inteiro é de uma dinâmica. Scripts mistos (lista associada, data
  por extenso junto, duas dinâmicas) ficam como "personalizado" e não são reescritos.
  Ao salvar, o script volta igual, nos mesmos eventos, com o mesmo seletor do `nbens` e
  o mesmo argumento do "não ajuizar". Únicas diferenças: o #42 passa a usar os códigos
  certos no tipo de pessoa, e o #173 preenche os dois alvos em todos os eventos.
- **Opções:** linha `Rótulo|Retorno 1|Retorno 2|…|Retorno 5`. Salvar não recria mais as
  opções: atualiza pelo rótulo e mantém o código do legado e os retornos 2–5 que a linha
  não trouxer. Quebras de linha dos retornos (HTML) viram espaço na caixa.
- **Eventos:** salvar não descarta mais data/valor por extenso em campo sem máscara; só
  o incompatível com a máscara (data em campo de valor e vice-versa). Os presets de data
  e valor aparecem para qualquer campo de texto.
- **Inserir marcador:** acima dos parágrafos, escolhe o campo e insere `@CAMPOn@`,
  `@Campon@`, `@campon@`, `@campon_pre@` ou `@campon_pos@` no editor clicado.
- A tela funciona mesmo sem o CKEditor carregado (antes, o script parava inteiro).
Testes: `AdminCampoDinamicaTest`.

## 4d. Fase 3 — feita em 24/10/2026 (importação de DOCX com IA)

`PeticaoModeloAiBuilderService` (esquema + instruções) e
`NormalizedTipoController::createFromAi/applyAiDynamics`:
- O agente devolve, por campo: **marcador** (MAIÚSCULAS / INICIAIS / COMO_DIGITADO,
  conforme o trecho no documento → `@CAMPOn@`/`@Campon@`/`@campon@`), **extenso**
  (data por extenso, data de hoje por extenso na data da petição, valor por extenso),
  **opções com retornos** (`{rotulo, retornos[]}`) e uma **dinâmica** (os mesmos tipos
  da tela, referindo os outros campos pelos tokens temporários).
- Instruções: trecho opcional → SELECT SIM/NÃO com o HTML do trecho no Retorno 1, e o
  token do SELECT no lugar do trecho (na montagem, select criado no sistema novo traz o
  retorno, agora sem escapar o HTML); dados que só valem com SIM → mostrar com SIM;
  qualificação → PESSOA + CPF/CNPJ + QUALIFICAÇÃO; itens repetidos → quantidade +
  próxima parcela; datas/valores → máscara e extenso.
- Na criação, as dinâmicas viram os scripts do motor (via `CampoDinamica::scripts`),
  ficam gravadas para a tela de edição, e os campos que só aparecem por dinâmica nascem
  ocultos. A revisão antes de gravar mostra marcador, extenso, opções com retorno e a
  dinâmica de cada campo.
- **Troca dos trechos no HTML do Word (bug antigo):** era um "substituir tudo" que ignora
  maiúsculas e minúsculas. A UF "PE" virava token dentro de "pessoa" e de "pede", as três
  parcelas "R$ 1.500,00" viravam o mesmo campo, "Recife" do endereço virava a comarca.
  Agora a troca é por palavra inteira, fora das tags, na ordem do documento (cada
  ocorrência vai para o próximo campo com aquele texto) e com o trecho mais longo
  primeiro (a frase da qualificação engole o CPF dela).
- **Montagem:** o tipo de pessoa (`alteraInput`) só limpa o documento se o número não
  servir para o tipo escolhido; antes apagava o CPF/CNPJ que veio do NEO.

**Teste real** (DOCX de exemplo com qualificação, tabela de 3 parcelas, valor por
extenso, parágrafo de segredo de justiça e fecho com data; gpt-5.6, cerca de 100 s): o
agente propôs PESSOA/CPF-CNPJ/QUALIFICAÇÃO com qualificação e tipo de pessoa, quantidade
de parcelas (2 campos por item) com próxima parcela, segredo de justiça como SIM/NÃO com
o parágrafo, valor por extenso, data de hoje no fecho e cópia comarca → local do fecho.
Modelo criado numa transação desfeita (nada ficou no banco); tela de montagem e peça
conferidas no headless.
**Limitação:** a tabela de parcelas do Word fica como está. Linhas de parcelas não
escolhidas saem com o número e células vazias. Para a linha sumir, ajuste na tela:
prefixo/sufixo da linha nos campos e marcadores `_pre`/`_pos` (botões "Prefixo/Sufixo").
Testes: `PeticaoModeloAiBuilderServiceTest` (normalização e troca dos trechos),
`AdminAiModeloDynamicsTest` (criação até a peça).

## 5. Proposta (em ordem)

**Fase 1 — corrigir a montagem do texto** (afeta peças de quase todos os modelos)
1. Forma do marcador → maiúsculas, iniciais ou como digitado, igual ao legado.
2. `@campoN_pre@` / `@campoN_pos@` + prefixo e sufixo só quando há valor, com HTML.
3. Limpeza de vírgulas soltas.
4. Encurtamento "…EM ALIENAÇÃO FIDUCIÁRIA" (ou uma tabela de trocas configurável).
5. Bug da data no importador.

**Fase 2 — tela de edição:** presets para cada dinâmica do item 3 (retorno da opção,
quantidade, cópia, CPF/CNPJ, qualificação, "não ajuizar", linhas de tabela, forma do
marcador), sem escrever script.

**Fase 3 — agente de IA:** ampliar o esquema para propor essas dinâmicas a partir do
DOCX (opções com retorno, condicionais, repetição, qualificação, extensos, forma do
marcador), sempre para revisão na tela antes de gravar.

## 4e. Máscaras de documento e CEP — 25/10/2026
Pedido do Fabio (print do #34 AÇÃO DE EXECUÇÃO: CPF/CNPJ e CEP sem formato).
- Migration `2026_10_25_000100` + `App\Support\CampoMascaraPorRotulo`: em campos TEXT,
  rótulo com CPF e CNPJ → `cpf_cnpj`; só CPF → `cpf`; só CNPJ → `cnpj`; CEP → `cep`.
  Troca só máscara ausente ou incompatível (33 campos). Inclui campos "CPF/CNPJ" que
  tinham máscara só de CPF, que cortava o CNPJ em 11 dígitos.
- Montagem: um select com as opções FÍSICA e JURÍDICA (sem `alteraInput`) liga-se ao
  campo de documento seguinte no formulário (sem ele, ao anterior). FÍSICA → CPF,
  JURÍDICA → CNPJ, sem escolha → pelo tamanho. O número vindo do NEO não é apagado;
  se não servir para o tipo, fica formatado pelo tamanho até ser corrigido.
- A formatação já roda ao carregar (dados do NEO) e ao digitar.
Conferido no Chrome (#34, processo 32216005): CPF 346.238.504-63, CEP 54330-065;
FÍSICA limita a CPF. No #17, o CPF/CNPJ vem antes do PESSOA e também fica ligado.

## 4f. Tipografia das peças — 25/10/2026
Pedido do Fabio: no editor e no PDF, letra pequena e espaços enormes entre parágrafos,
comparando com o legado (peça MANUEL JUNIO SANTOS em produção).
- **Causa da letra pequena:** os modelos gravam o tamanho em px (Tahoma 10px) e o novo
  mostrava 10px de verdade, ou seja, 7,5pt. O html2pdf do legado também imprime px no
  tamanho real (12px = 9pt); as peças do legado só saíam legíveis porque os usuários
  aumentavam a letra para 11-12px no editor (os modelos continuam em 10px nos dois bancos).
- **Decisão (Fabio, 25/10):** o número vale em pt (10px → 10pt), a proporção que o usuário
  via no editor, sem alterar os 217 modelos. Texto sem tamanho definido: 11pt, como o
  legado (`PeticaoTipografia::BASE_FONT_PT`, no editor, na prévia, no PDF e no Word).
- **Causa dos espaços:** o novo usava entrelinha 1.6 e 12px de margem depois de cada
  parágrafo, inclusive nas linhas em branco `<p>&nbsp;</p>` que os modelos já trazem. O
  legado usava parágrafo sem margem e entrelinha de 115% (`inc/getpdf.php`).
- **Correção (`App\Support\PeticaoTipografia`):** px vira pt a partir de 10px (os 9px do
  contato do cabeçalho ficam); parágrafo sem margem; entrelinha 1.15 no editor
  (`public/ckeditor/contents.css`), na prévia da montagem, na impressão, no PDF
  (Playwright) e no Word (DOCX: `w:after=0`, `w:line=276`). Saíram as trocas que forçavam
  115% → 160%. A conversão vale na montagem (peças novas), ao abrir no editor e na
  exportação (peças antigas, sem regravar o banco).
- **Cabeçalho antigo embutido na peça:** o contato era empurrado para a direita com
  dezenas de `&nbsp;` e quebrava linha ("CEP: 52.030-" / "195"). Na exportação, esses
  espaços do início das linhas saem e a célula de texto do cabeçalho fica alinhada à
  direita (`trimRightAlignedPadding`).
- **Cache do editor:** o CKEditor pedia o `contents.css` com um carimbo fixo, então o
  navegador seguia com o CSS antigo. Agora o carimbo segue a data do arquivo
  (`resources/views/partials/ckeditor.blade.php`).
- Fonte do editor: a lista de tamanhos passou a ser em pt (8 a 20pt).
Medido na peça 1248 (processo 193673005): PDF de 13 para 9 páginas (legado: 10), corpo a
10pt. No Word, 10pt, sem espaço depois e entrelinha 1.15. No editor, 10pt, entrelinha
18,4px e margem 0.
Varredura dos 25 modelos mais usados (peça mais recente de cada um no legado, PDF do legado
pelo mesmo html2pdf × PDF novo): 22 com o mesmo número de páginas; #18 INICIAL DE BUSCA
6 → 8, #89 INICIAL BUSCA 3 → 4 e #17 REQUERIMENTO 1 → 2, pela letra maior (11-12pt contra
8-9pt no legado). Entrelinha e margens iguais às do legado.

## 4g. Importação de DOCX com IA: formatação e notas de rodapé — 25/10/2026
Pedido do Fabio: o modelo #243 (NOVA INICIAL – NÃO PROCURADO) veio sem estilo no editor,
e as notas de rodapé são o maior desafio. Os modelos do legado não têm notas; é recurso novo.
- **Causa da perda de estilo:** as duas conversões do Word falhavam no Laragon e a
  importação caía no texto puro. (1) O caminho do perfil do LibreOffice ia como
  `file:///C%3A/...` e o `escapeshellarg` do Windows troca `%` por espaço: o LibreOffice
  fechava com erro. (2) O PhpWord gravava temporários em `C:\Windows\Temp`, sem permissão
  para o Apache. Com o texto puro, a IA devolvia o corpo só com `<p>`.
- **Correção (`WordImportService`):** caminho do perfil sem codificar no Windows; PhpWord
  com temporários na pasta da conversão; falhas registradas no log. O LibreOffice é a
  fonte principal; o PhpWord só entra quando o LibreOffice devolve só o texto ou achata
  uma tabela.
- **Limpeza (`App\Support\WordHtmlCleaner`):** `<font>` vira `<span>`, títulos viram
  parágrafo, `align` vira `text-align`, e saem cabeçalho/rodapé nativos do Word, estilos
  sem efeito e Arial/Calibri. Os pedaços de texto com o mesmo estilo são juntados, o que
  reduz o HTML (192KB → 60KB no #243). As caixas de texto do Word, que tinham posição
  absoluta, viram bloco com borda à direita.
- **Notas (opção A, escolhida pelo Fabio):** a chamada fica no texto como `<sup>N</sup>` e
  as notas vão para o fim da peça, numa lista `peticao-notas` (linha, letra 9pt),
  numeradas pela ordem das chamadas. Uma nota que é só um endereço web fica alinhada à
  esquerda.
- **IA:** ela não copia mais o corpo. O corpo é sempre o HTML do Word, e o sistema troca
  cada `texto_original` pelo token, inclusive quando o trecho atravessa negrito
  (`<b>BANCO X</b>, pessoa jurídica…`: o token entra e as tags continuam fechadas). A
  instrução pede só a parte variável, por exemplo a comarca e não o endereçamento inteiro.
Medido no DOCX do #243, com a IA de verdade: 23 a 33 campos por rodada, todos colocados
no texto, com negrito, 52 parágrafos centralizados, recuos, tabela e as 9 notas no fim.
O #243 já gravado continua sem estilo: é preciso importar o DOCX de novo.

