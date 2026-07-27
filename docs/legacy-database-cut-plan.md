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
