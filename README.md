# Peticao Facil

## Build/Deploy

This project uses Webpack to bundle/minify JS/CSS while keeping the
per-page source files organized.

## Requirements (Production)

- Node.js (LTS recommended) with `npm` in PATH
- PHP 7.2+ runtime (app requirement)

### Local/CI build (bundle)

```
npm run build:prod
```

This generates minified assets in `public/js` and `public/css`:
- `public/js/main.min.js`
- `public/css/main.min.css`

The per-page sources remain in:
- `public/js/pages/*.js`
- `public/css/*.css`
- `assets/scss/*.scss` (optional new SCSS sources)

### Server deploy (git pull)

On the server, run one of these scripts after pulling:

```
./scripts/deploy-server.sh
```

Or on Windows/PowerShell:

```
.\scripts\deploy-server.ps1
```

Both scripts run:
- `git pull`
- `npm run build:prod`

## SCSS organization (optional)

Add your SCSS files in `assets/scss/` and import them into
`assets/scss/main.scss` (e.g. `@import "./pages/home";`).

## Rollback

If you need to rollback, checkout a previous commit and re-run the build:

```
git checkout <commit-hash>
npm run build:prod
```

## Post-deploy checklist

- Login/logout works
- Create a new petition (form + paragraphs)
- Open editor and save (auto-save)
- Export PDF
- Admin areas: users/setor/clientes/servidor

## Performance & Monitoring

- Verify home page loads quickly (latest petitions list)
- Check browser console for JS errors
- Check server logs for PHP errors

## Log maintenance

- Logs are ignored in git (`storage/logs/`)
- Optional cleanup:
  - delete old log files periodically
  - ensure logs do not grow indefinitely
