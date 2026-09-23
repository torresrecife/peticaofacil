# Modelo Stone — implantação

O pacote `database/data/stone-execucao-titulo-extrajudicial.json` não contém IDs do banco local. O comando resolve cliente, setor e servidor por suas chaves estáveis e recria os tokens conforme os IDs do ambiente de destino.

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

   As tabelas de clientes, setores e perfis NEO são apenas consultadas para
   resolver as dependências (`STONE`, `RCJ - VAREJO` e `NEO`).

5. Homologue formulário, prévia, Word e PDF. Depois execute novamente com ativação:

   ```bash
   php artisan peticao:modelo-stone-import --activate
   ```

O comando é idempotente: identifica o modelo pelo slug, atualiza seus dados e recria campos e parágrafos dentro de uma única transação. Qualquer falha reverte a operação completa.
