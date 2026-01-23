param()

$npm = "C:\laragon\bin\nodejs\node-v18\npm.cmd"

if (-not (Test-Path $npm)) {
  Write-Error "npm not found at $npm"
  exit 1
}

& $npm run build:prod
if ($LASTEXITCODE -ne 0) {
  exit $LASTEXITCODE
}
