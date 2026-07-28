# Plano final de corte do legado

Data de referencia: Tuesday, July 28, 2026

## Estado final do runtime

O runtime web principal agora depende apenas da trilha normalizada para:

- login principal
- painel
- montagem
- editor
- pecas salvas
- favoritos
- listas
- modelos
- perfis SQL
- admin

As compatibilidades antigas de rota foram removidas do `routes/web.php`.

Estado esperado das URLs antigas:

- `404 Not Found`

## Tabelas legadas arquivadas na base local

- `tp_grupo_tb` -> `tp_grupo_tb_archive_20260727`
- `tp_lista_tb` -> `tp_lista_tb_archive_20260727`
- `tp_config_db` -> `tp_config_db_archive_20260727`
- `tp_funda_tb` -> `tp_funda_tb_archive_20260727`
- `tp_inputs_tb` -> `tp_inputs_tb_archive_20260727`
- `tp_dados_tb` -> `tp_dados_tb_archive_20260727`
- `tp_tipo_tb` -> `tp_tipo_tb_archive_20260727`
- `tp_pecas_tb` -> `tp_pecas_tb_archive_20260727`
- `tp_usu_tb` -> `tp_usu_tb_archive_20260727`

Na base local, elas ja sairam do banco operacional por rename controlado.

## Legado que ainda existe por necessidade tecnica

### Sync/manual

- `usuarios:sync-legado`
- `usuarios:sync-vinculos`
- `peticao:sync-pecas`
- `peticao:sync-legado`
- `listas:sync-legado`
- `servidores:sync-legado`

### Espelhamento opcional ainda disponivel por config

- usuarios
- pecas
- modelos
- listas
- perfis SQL

Todos permanecem desligados no baseline final.

## Configuracao final esperada

```env
LEGACY_PECAS_MIRROR=false
LEGACY_MODELOS_MIRROR=false
LEGACY_LISTAS_MIRROR=false
LEGACY_SQL_CONFIGS_MIRROR=false
LEGACY_USERS_MIRROR=false
LEGACY_USERS_AUTH_FALLBACK=false
```

## Auditoria operacional

Comando:

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan legacy:cut-readiness
```

Leitura esperada:

- listas arquivadas
- servidores SQL arquivados
- modelos arquivados
- pecas arquivadas
- usuarios arquivados
- mirrors legados desligados

## Classes legadas que ainda fazem sentido

### Permanecem por sync/backfill/manual

- `LegacyListaGrupo`
- `LegacyListaItem`
- `Tipo`
- `Paragrafo`
- `InputCampo`
- `SqlServerConfig`
- `Peca`
- `LegacyUser`

Elas ja nao fazem parte do runtime web normal. Servem para:

- sync/manual
- rollback controlado
- historico tecnico

## Proximo corte tecnico natural

Se a equipe quiser continuar limpando o repositório:

1. podar comandos e services de sync que nao tenham mais uso operacional
2. revisar models legados que ficaram apenas como suporte de arquivo
3. decidir se o historico tecnico legado permanece no mesmo repositório ou vai para pacote/arquivo separado
