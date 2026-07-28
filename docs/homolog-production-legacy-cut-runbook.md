# Runbook final de homologacao/producao

Data de referencia: Tuesday, July 28, 2026

## Estado alvo

O baseline correto do projeto agora e este:

- runtime web apenas com rotas normalizadas
- admin apenas com trilha normalizada
- sem rotas `legacy.*` ativas
- sem superfícies antigas de admin por URL legado
- tabelas legadas operacionais fora do banco ativo
- legado restrito a sync/manual e historico tecnico

## Configuracao alvo do ambiente

No `.env`:

```env
LEGACY_PECAS_MIRROR=false
LEGACY_MODELOS_MIRROR=false
LEGACY_LISTAS_MIRROR=false
LEGACY_SQL_CONFIGS_MIRROR=false
LEGACY_USERS_MIRROR=false
LEGACY_USERS_AUTH_FALLBACK=false
```

## Comandos obrigatorios

```powershell
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan config:clear
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan cache:clear
& "C:\laragon\bin\php\php-7.2.34-nts-Win32-VC15-x64\php.exe" artisan legacy:cut-readiness
```

## Validacao funcional minima

Validar no navegador:

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

## Resposta esperada para URLs antigas

As antigas superficies web devem responder:

- `404 Not Found`

Isso inclui:

- antigas rotas `legacy.*`
- antigas rotas de admin legado de modelos
- antigas rotas de admin legado de servidores

## Evidencia minima

Guardar:

- saida de `artisan legacy:cut-readiness`
- checklist funcional assinado

Sem isso, o ambiente nao esta alinhado ao baseline final.
