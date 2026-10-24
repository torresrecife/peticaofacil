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
