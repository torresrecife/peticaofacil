$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$stagingRoot = Join-Path $root 'staging\root-promotion'
$stagingPublic = Join-Path $stagingRoot 'public'
$laravelRoot = Join-Path $root 'laravel6'
$laravelPublic = Join-Path $laravelRoot 'public'
$legacyPublic = Join-Path $root 'public'

function Copy-DirectoryContents {
    param(
        [Parameter(Mandatory = $true)][string]$Source,
        [Parameter(Mandatory = $true)][string]$Destination
    )

    if (!(Test-Path $Source)) {
        throw "Source path not found: $Source"
    }

    New-Item -ItemType Directory -Force -Path $Destination | Out-Null
    Copy-Item -Path (Join-Path $Source '*') -Destination $Destination -Recurse -Force
}

if (Test-Path $stagingRoot) {
    Remove-Item -Recurse -Force $stagingRoot
}

New-Item -ItemType Directory -Force -Path $stagingRoot | Out-Null

# Base Laravel root structure
$rootEntries = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'resources',
    'routes',
    'storage',
    'tests',
    'vendor',
    '.env',
    '.env.example',
    '.editorconfig',
    '.gitattributes',
    '.gitignore',
    '.styleci.yml',
    'artisan',
    'composer.json',
    'composer.lock',
    'package.json',
    'package-lock.json',
    'phpunit.xml',
    'server.php',
    'webpack.mix.js',
    'README.md'
)

foreach ($entry in $rootEntries) {
    $source = Join-Path $laravelRoot $entry
    $destination = Join-Path $stagingRoot $entry

    if (Test-Path $source) {
        if ((Get-Item $source) -is [System.IO.DirectoryInfo]) {
            Copy-DirectoryContents -Source $source -Destination $destination
        } else {
            New-Item -ItemType Directory -Force -Path (Split-Path -Parent $destination) | Out-Null
            Copy-Item -Path $source -Destination $destination -Force
        }
    }
}

# Start with Laravel public
Copy-DirectoryContents -Source $laravelPublic -Destination $stagingPublic

# Overlay required legacy public assets
$legacyPublicPreserve = @(
    'ckeditor',
    'ckfinder',
    'img'
)

foreach ($entry in $legacyPublicPreserve) {
    $source = Join-Path $legacyPublic $entry
    $destination = Join-Path $stagingPublic $entry
    Copy-DirectoryContents -Source $source -Destination $destination
}

$manifest = @"
Root promotion staging generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

Base source:
- laravel6/*

Public base:
- laravel6/public/*

Legacy public assets preserved:
- public/ckeditor
- public/ckfinder
- public/img

Legacy public assets intentionally excluded:
- public/css
- public/js
- public/inc
"@

Set-Content -Path (Join-Path $stagingRoot 'STAGING_MANIFEST.txt') -Value $manifest -Encoding UTF8

# Adjust .env for promoted root layout
$envPath = Join-Path $stagingRoot '.env'
if (Test-Path $envPath) {
    $envContent = Get-Content $envPath -Raw
    $envContent = [System.Text.RegularExpressions.Regex]::Replace(
        $envContent,
        '(?m)^LEGACY_CKFINDER_BASE_URL=.*$',
        'LEGACY_CKFINDER_BASE_URL=/peticaofacil/ckfinder/userfiles/'
    )
    Set-Content -Path $envPath -Value $envContent -Encoding UTF8
}

# Root entry files for environments that still point the document root at the project root
$rootIndex = @'
<?php

require __DIR__ . '/public/index.php';
'@

Set-Content -Path (Join-Path $stagingRoot 'index.php') -Value $rootIndex -Encoding UTF8

$rootHtaccess = @'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /peticaofacil/

    RewriteRule ^public/ - [L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
'@

Set-Content -Path (Join-Path $stagingRoot '.htaccess') -Value $rootHtaccess -Encoding UTF8

$rootLogin = @'
<?php

header('Location: /peticaofacil/login');
exit;
'@

Set-Content -Path (Join-Path $stagingRoot 'login.php') -Value $rootLogin -Encoding UTF8

$rootLogout = @'
<?php

header('Location: /peticaofacil/legacy/logout');
exit;
'@

Set-Content -Path (Join-Path $stagingRoot 'sair.php') -Value $rootLogout -Encoding UTF8

Write-Host "Staging prepared at: $stagingRoot"
