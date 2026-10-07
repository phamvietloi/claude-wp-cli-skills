# Returns the dev sandbox to its freshly seeded state.
#   dev\reset.ps1          wipe both volumes, reinstall, re-seed (slow, fully clean)
#   dev\reset.ps1 -Quick   re-import the database snapshot taken after seeding and empty the inbox
param([switch]$Quick)

$ErrorActionPreference = 'Stop'
$dev = $PSScriptRoot
$wp = Join-Path $dev 'wp.ps1'
$snapshot = '/var/www/html/wp-content/.ht-wpcli-dev-seed.sql'

if ($Quick) {
    & $wp eval "exit( file_exists( '$snapshot' ) ? 0 : 1 );" --skip-wordpress 2>$null
    if ($LASTEXITCODE -eq 0) {
        & $wp db import $snapshot --skip-plugins --skip-themes
        if ($LASTEXITCODE -ne 0) { throw 'Snapshot import failed' }
        & $wp cache flush --skip-plugins --skip-themes | Out-Null
        $mailPort = (Get-Content "$dev\.env" | Where-Object { $_ -match '^MAIL_PORT=(.*)$' } | ForEach-Object { $Matches[1].Trim() })
        Invoke-RestMethod -Method Delete -Uri "http://127.0.0.1:$mailPort/api/v1/messages" | Out-Null
        Write-Host 'Database restored from the seed snapshot; inbox emptied.'
        exit 0
    }
    Write-Warning 'No seed snapshot found; doing a full reset instead.'
}

docker compose -f "$dev\docker-compose.yml" down --volumes
if ($LASTEXITCODE -ne 0) { throw 'docker compose down failed' }
& "$dev\setup.ps1"
