# Plano de corte final das tabelas legadas

Data de referencia: 2026-07-27

## Estado atual

O runtime principal do sistema ja nao depende mais das tabelas legadas para:

- login principal
- painel
- montagem
- editor
- pecas salvas
- favoritos
- listas
- modelos
- perfis SQL

As tabelas legadas remanescentes ficaram reduzidas a:

- sync/backfill manual
- rollback controlado
- historico tecnico
- compatibilidade residual explicitamente configuravel

## Tabelas legadas por situacao

### 1. Prontas para sair do runtime normal

- `tp_pecas_tb`
- `tp_tipo_tb`
- `tp_funda_tb`
- `tp_inputs_tb`
- `tp_dados_tb`
- `tp_grupo_tb`
- `tp_lista_tb`
- `tp_config_db`
- `tp_usu_tb`

Elas ainda existem no banco, mas o fluxo normalizado ja e o principal.

### 2. Ainda usadas para sync/manual

- `tp_usu_tb`
  - `usuarios:sync-legado`
  - `usuarios:sync-vinculos`
- `tp_pecas_tb`
  - `peticao:sync-pecas`
- `tp_tipo_tb`, `tp_funda_tb`, `tp_inputs_tb`, `tp_dados_tb`
  - `peticao:sync-legado`
- `tp_grupo_tb`, `tp_lista_tb`
  - `listas:sync-legado`
- `tp_config_db`
  - sync/manual de perfis SQL antigos

## Toggles que precisam permanecer desligados

Em homologacao e producao, o alvo final e manter:

```env
LEGACY_PECAS_MIRROR=false
LEGACY_MODELOS_MIRROR=false
LEGACY_LISTAS_MIRROR=false
LEGACY_SQL_CONFIGS_MIRROR=false
LEGACY_USERS_MIRROR=false
LEGACY_USERS_AUTH_FALLBACK=false
```

## Ordem recomendada do corte final

### Fase 1: congelar escrita legada

Confirmar em homologacao/producao:

- `LEGACY_USERS_MIRROR=false`
- `LEGACY_USERS_AUTH_FALLBACK=false`
- `LEGACY_LISTAS_MIRROR=false`
- `LEGACY_SQL_CONFIGS_MIRROR=false`
- `LEGACY_MODELOS_MIRROR=false`
- `LEGACY_PECAS_MIRROR=false`

### Fase 2: validar fluxos principais

Validar:

- login
- troca de senha
- usuarios
- listas
- servidores SQL
- modelos
- montagem
- salvar peticao
- `/pecas`
- exportacao Word/PDF

### Fase 3: manter apenas circuitos manuais

Depois da validacao:

- `LegacyUser` fica so para comandos de sync
- `LegacyListaGrupo` e `LegacyListaItem` ficam so para `listas:sync-legado`
- `SqlServerConfig` fica so para sync/manual
- `Tipo`, `Paragrafo`, `InputCampo`, `InputDado` ficam so para `peticao:sync-legado`
- `Peca` legado fica so para `peticao:sync-pecas`

### Fase 4: arquivamento e remocao

So depois de backup e janela formal:

1. exportar dump das tabelas legadas
2. mover para schema de arquivo ou manter apenas backup externo
3. remover tabelas legadas do banco operacional

## Validacao objetiva de `tp_pecas_tb` fora do runtime web

Em 27 de julho de 2026, o runtime web ja opera assim:

- `/pecas` consulta `peticoes`
- `/pecas/{peca}/editar` usa apenas `legacy_peca_id` em `peticoes`
- save normalizado nao reaproveita mais `peca_id`
- escrita em `tp_pecas_tb` depende de `LEGACY_PECAS_MIRROR=true`
- com `LEGACY_PECAS_MIRROR=false`, `tp_pecas_tb` fica restrita a:
  - `peticao:sync-pecas`
  - `LegacyPecaSyncService`
  - `LegacyPecaMirrorService` quando reativado por configuracao

## Sequencia operacional de arquivamento/remocao das tabelas legadas

### Etapa A: congelamento final

Confirmar em homologacao e producao:

```env
LEGACY_PECAS_MIRROR=false
LEGACY_MODELOS_MIRROR=false
LEGACY_LISTAS_MIRROR=false
LEGACY_SQL_CONFIGS_MIRROR=false
LEGACY_USERS_MIRROR=false
LEGACY_USERS_AUTH_FALLBACK=false
```

### Etapa B: backup formal

Executar dump apenas das tabelas legadas:

- `tp_usu_tb`
- `tp_pecas_tb`
- `tp_tipo_tb`
- `tp_funda_tb`
- `tp_inputs_tb`
- `tp_dados_tb`
- `tp_grupo_tb`
- `tp_lista_tb`
- `tp_config_db`

### Etapa C: janela de verificacao em somente leitura

Manter as tabelas legadas no banco por uma janela curta, apenas para:

- comandos de sync
- rollback controlado
- conferencia historica

Sem leitura/escrita do runtime web principal.

### Etapa D: retirada operacional

1. bloquear execucao dos comandos de sync em rotina normal
2. mover dumps para armazenamento de arquivo
3. opcionalmente mover as tabelas para schema de arquivo
4. remover as tabelas do banco operacional

## Ordem recomendada de drop

1. `tp_grupo_tb`
2. `tp_lista_tb`
3. `tp_config_db`
4. `tp_funda_tb`
5. `tp_inputs_tb`
6. `tp_dados_tb`
7. `tp_tipo_tb`
8. `tp_pecas_tb`
9. `tp_usu_tb`

Racional:

- listas e SQL ja sairam do runtime principal
- modelos dependem entre si e devem sair juntos
- `tp_pecas_tb` e `tp_usu_tb` ficam por ultimo por causa do historico

## Checklist operacional antes do drop

- [ ] todos os toggles legados desligados
- [ ] zero leitura legada no runtime web
- [ ] zero escrita legada no runtime web
- [ ] comandos de sync testados e opcionais
- [ ] backup das tabelas legado confirmado
- [ ] homologacao validada
- [ ] janela de corte aprovada

## Proximo passo tecnico recomendado

1. revisar `LegacyUser`, `LegacyListaGrupo`, `LegacyListaItem`, `SqlServerConfig`, `Tipo`, `Paragrafo`, `InputCampo` e `Peca` para garantir que ficaram fora do runtime web;
2. depois preparar a remocao controlada das rotas residuais `legacy.*` que ainda sobraram.

## Auditoria das classes legadas remanescentes

### Fora do runtime web normal

- `LegacyListaGrupo`
  - usado por `listas:sync-legado`
  - usado por `LegacyListaMirrorService` quando `LEGACY_LISTAS_MIRROR=true`
- `LegacyListaItem`
  - usado por `listas:sync-legado`
  - usado por `LegacyListaMirrorService` quando `LEGACY_LISTAS_MIRROR=true`
- `Tipo`
  - usado por `peticao:sync-legado`
  - usado pelo admin residual de modelos
- `Paragrafo`
  - usado por `peticao:sync-legado`
  - usado pelo admin residual de modelos
- `InputCampo`
  - usado por `peticao:sync-legado`
  - usado pelo admin residual de modelos

### Ainda residuais no runtime web

- `Peca`
  - compatibilidade explicita em `/pecas/{peca}/editar`
  - sync/backfill em `peticao:sync-pecas`
- `SqlServerConfig`
  - compatibilidade explicita em `/admin/servidores/{id}/edit`
  - sync/manual via `servidores:sync-legado`
  - espelhamento opcional quando `LEGACY_SQL_CONFIGS_MIRROR=true`

## Rotas `legacy.*` restantes

### Podadas para compatibilidade fina

- `legacy.login.file`
- `legacy.logout.file`
- `legacy.peticoes.editor.edit`

### Ainda mantidas por compatibilidade externa de modelos

- `legacy.peticoes.modelos.show`
- `legacy.peticoes.modelos.compose`
- `legacy.peticoes.modelos.editor.create`
- `legacy.peticoes.modelos.saved.store`
- `legacy.peticoes.modelos.editor.save`
- `legacy.peticoes.modelos.editor.export.pdf`
- `legacy.peticoes.modelos.editor.export.word`

Essas rotas ja nao carregam `Tipo` diretamente no runtime web. Elas resolvem o modelo normalizado por `legacy_tipo_id` e delegam para a trilha `peticoes.normalized.*`.

`legacy.peticoes.modelos.show` ja pode ser tratado como redirect explicito para `peticoes.normalized.show`.

## Sync manual necessario antes do corte final de servidores SQL

Para tirar `SqlServerConfig` do admin normalizado, a trilha nova precisa estar preenchida:

```powershell
& "C:\\laragon\\bin\\php\\php-7.2.34-nts-Win32-VC15-x64\\php.exe" artisan servidores:sync-legado
```
