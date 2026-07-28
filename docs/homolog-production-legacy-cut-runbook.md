# Runbook de corte legacy em homologacao/producao

Data de referencia: Tuesday, July 28, 2026

## Objetivo

Aplicar em homologacao/producao a mesma politica ja validada localmente:

- compatibilidade legacy desligada por padrao
- auditoria de rotas legacy ligada
- validacao operacional do runtime normalizado
- janela curta de observacao
- remocao fisica das rotas `legacy.*` apenas se a telemetria vier zerada ou irrelevante

## 1. Configuracao alvo

No `.env` do ambiente:

```env
LEGACY_PECAS_MIRROR=false
LEGACY_MODELOS_MIRROR=false
LEGACY_LISTAS_MIRROR=false
LEGACY_SQL_CONFIGS_MIRROR=false
LEGACY_USERS_MIRROR=false
LEGACY_USERS_AUTH_FALLBACK=false
LEGACY_PUBLIC_MODEL_ROUTE_COMPAT=false
LEGACY_PUBLIC_PIECE_EDITOR_COMPAT=false
LEGACY_ADMIN_SQL_ROUTE_COMPAT=false
LEGACY_ADMIN_MODEL_ROUTE_COMPAT=false
LEGACY_ROUTE_AUDIT=true
```

## 2. Aplicacao no ambiente

Depois de atualizar o `.env`:

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan config:clear
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan cache:clear
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan legacy:cut-readiness
```

Resultado esperado:

- mirrors legacy desligados
- compatibilidades de rotas desligadas
- estado das tabelas normalizadas coerente

## 3. Validacao funcional obrigatoria

Executar no navegador:

- `/login`
- `/painel`
- `/peticoes`
- `/pecas`
- `/admin/usuarios`
- `/admin/listas`
- `/admin/servidores-normalizados`
- `/admin/modelos-normalizados`

Fluxos obrigatorios:

- login
- logout
- troca de senha
- criar/editar usuario
- criar/editar lista
- criar/editar servidor SQL normalizado
- criar/editar modelo normalizado
- montar peticao
- salvar peticao
- exportar Word/PDF

## 4. Janela de observacao

Manter:

```env
LEGACY_ROUTE_AUDIT=true
```

por pelo menos 7 dias corridos.

Durante a janela, os hits nas rotas residuais sao registrados no log do Laravel com o evento:

- `legacy_route_hit`

Resumo operacional:

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan legacy:route-audit-report --days=7
```

## 5. Rotas que ainda estao sob observacao

### Publicas

- `legacy.login.file`
- `legacy.logout.file`
- `legacy.peticoes.modelos.show`
- `legacy.peticoes.modelos.compose`
- `legacy.peticoes.modelos.editor.create`
- `legacy.peticoes.modelos.saved.store`
- `legacy.peticoes.modelos.editor.save`
- `legacy.peticoes.modelos.editor.export.pdf`
- `legacy.peticoes.modelos.editor.export.word`
- `legacy.peticoes.editor.edit`

### Admin residuais

- `admin.servidores.edit`
- `admin.servidores.update`
- `admin.modelos.edit`
- `admin.modelos.sync`
- `admin.modelos.update`
- `admin.modelos.paragrafos.store`
- `admin.modelos.paragrafos.update`
- `admin.modelos.campos.store`
- `admin.modelos.campos.update`

## 6. Criterio para remocao fisica das rotas

Pode remover o codigo das rotas acima se todos os pontos abaixo forem verdadeiros:

- toggles legacy continuam desligados
- `legacy:cut-readiness` segue OK
- `legacy:route-audit-report --days=7` vier zerado ou com hits irrelevantes/esperados
- nao houver consumidor externo conhecido dessas URLs
- homologacao/producao ja estiverem estaveis no runtime normalizado

## 7. Ordem recomendada da remocao fisica

1. remover rotas admin residuais de SQL
2. remover rotas admin residuais de modelos
3. remover `legacy.peticoes.editor.edit`
4. remover `legacy.peticoes.modelos.*`
5. por ultimo remover `legacy.login.file` e `legacy.logout.file`, se nao houver mais dependente externo

## 8. Evidencia minima antes do merge final

Guardar:

- saida de `artisan legacy:cut-readiness`
- saida de `artisan legacy:route-audit-report --days=7`
- checklist funcional assinado:
  - login
  - painel
  - peticoes
  - pecas
  - admin normalizado
  - exportacao

Sem isso, a remocao fisica das `legacy.*` fica sem base operacional suficiente.
