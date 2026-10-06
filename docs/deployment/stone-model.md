# Pacotes de modelo — exportar, importar e o modelo Stone

## O que vai no pacote

O botão **Exportar** (Admin → Modelos) gera um JSON na versão 2 do formato. Ele não
contém nenhum ID do banco de origem:

- cada campo recebe uma chave (`key`) derivada do rótulo, única dentro do modelo;
- toda citação a um campo vira referência por chave: `@campo123@` → `{{field:chave}}`
  nos textos (parágrafos, cabeçalho, rodapé, prefixo, sufixo, valor padrão, opções) e
  `campo123` / `repeteValor(123, …)` → `campo{{field_id:chave}}` / `{{field_id:chave}}`
  nos eventos e nas regras condicionais;
- cliente, setor e servidor vão pelo nome (`dependencies`), com o código do cliente e do
  setor como desempate;
- listas associadas vão pelo nome do grupo (`lista_grupo` no campo).

Um marcador que cita um campo de **outro** modelo não tem chave e fica como está; a
importação avisa quando encontra um.

## O que a importação exige

`peticao:modelo-stone-import` (usado também pelo botão **Importar**) resolve tudo contra
o banco do ambiente de destino antes de gravar. Se algo não puder ser resolvido sem
ambiguidade, nada é importado e a mensagem diz o que falta.

| Dependência | Como é encontrada | Se não encontrar |
| --- | --- | --- |
| Setor | pelo nome (`sector_name`); pacotes antigos, só pelo código, e apenas se o código for de um único setor | recusa |
| Cliente | pelo nome; havendo homônimos, pelo código e depois pelo setor | importa sem cliente e avisa; recusa se continuar ambíguo |
| Servidor | pelo nome | recusa (pacote sem servidor é aceito) |
| Lista associada | pelo nome do grupo | recusa — cadastre a lista antes |

Pacotes gerados antes da versão 2 que citam campos pelo número do ambiente de origem
(`@campo2829@`) são **recusados**: esses números não valem em outro banco. Exporte o
modelo de novo no ambiente de origem.

O comando é idempotente: identifica o modelo pelo `slug`, atualiza seus dados e recria
campos, opções e parágrafos dentro de uma única transação. Qualquer falha reverte tudo.

## Implantação do modelo Stone

O arquivo `database/data/stone-execucao-titulo-extrajudicial.json` é o pacote usado
quando o comando roda sem `--path`. Ele precisa estar na versão 2: no ambiente em que o
modelo foi montado, clique em **Exportar** no modelo Stone e substitua o arquivo pelo
JSON baixado.

1. Publique o código e execute as migrações normais da aplicação.
2. Confirme que existem o cliente `STONE`, o setor `RCJ - VAREJO` e o servidor `NEO`.
3. Valide sem alterar o banco:

   ```bash
   php artisan peticao:modelo-stone-import --dry-run
   ```

4. Faça o backup do banco e importe o modelo inativo:

   ```bash
   php artisan peticao:modelo-stone-import
   ```

   Como o importador atualiza o modelo e recria seus dados relacionados, faça
   o backup destas tabelas antes do comando:

   - `peticao_modelos`
   - `peticao_modelo_campos`
   - `peticao_modelo_campo_opcoes`
   - `peticao_modelo_paragrafos`

   As tabelas de clientes, setores, listas e perfis NEO são apenas consultadas para
   resolver as dependências.

5. Homologue formulário, prévia, Word e PDF. Depois execute novamente com ativação:

   ```bash
   php artisan peticao:modelo-stone-import --activate
   ```

Para outro pacote, informe o caminho: `php artisan peticao:modelo-stone-import --path=/caminho/modelo.json --dry-run`.
