# Brings the dev sandbox up and makes sure it is installed and seeded. Safe to re-run.
#   dev\setup.ps1
$ErrorActionPreference = 'Stop'
$dev = $PSScriptRoot
$wp = Join-Path $dev 'wp.ps1'
$snapshot = '/var/www/html/wp-content/.ht-wpcli-dev-seed.sql'

if (-not (Test-Path "$dev\.env")) { Copy-Item "$dev\.env.example" "$dev\.env" }
$cfg = @{}
Get-Content "$dev\.env" | Where-Object { $_ -match '^\s*([A-Z_]+)=(.*)$' } | ForEach-Object { $cfg[$Matches[1]] = $Matches[2].Trim() }
$siteUrl = "http://localhost:$($cfg.SITE_PORT)"
$mailUrl = "http://127.0.0.1:$($cfg.MAIL_PORT)"

function Invoke-Wp {
    # Runs WP-CLI in the sandbox and stops the script on failure.
    $out = & $wp @args
    if ($LASTEXITCODE -ne 0) { throw "wp $($args -join ' ') failed (exit $LASTEXITCODE)" }
    $out
}

Write-Host '==> Starting containers'
docker compose -f "$dev\docker-compose.yml" up -d --quiet-pull
if ($LASTEXITCODE -ne 0) { throw 'docker compose up failed' }

Write-Host '==> Waiting for WordPress files and the database'
$ready = $false
foreach ($i in 1..60) {
    & $wp core version 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) {
        & $wp db check 2>$null | Out-Null
        if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) { throw 'WordPress did not become ready in time' }

& $wp core is-installed 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host '==> Installing WordPress'
    Invoke-Wp core install "--url=$siteUrl" '--title=Dev Store' "--admin_user=$($cfg.WP_ADMIN_USER)" `
        "--admin_password=$($cfg.WP_ADMIN_PASSWORD)" "--admin_email=$($cfg.WP_ADMIN_EMAIL)" --skip-email | Out-Null
    Invoke-Wp rewrite structure '/%postname%/' | Out-Null
}

& $wp plugin is-installed woocommerce
if ($LASTEXITCODE -ne 0) {
    Write-Host "==> Installing WooCommerce $($cfg.WC_VERSION)"
    & $wp plugin install woocommerce "--version=$($cfg.WC_VERSION)" --activate
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "WooCommerce $($cfg.WC_VERSION) is not available; installing the latest release instead."
        Invoke-Wp plugin install woocommerce --activate | Out-Null
    }
}
& $wp plugin is-active woocommerce
if ($LASTEXITCODE -ne 0) { Invoke-Wp plugin activate woocommerce | Out-Null }
Invoke-Wp wc update | Out-Null

if (-not ((Invoke-Wp wc hpos status) -match 'HPOS enabled\?: yes')) {
    Write-Host '==> Enabling HPOS'
    Invoke-Wp wc hpos enable | Out-Null
}

if (-not (& $wp option get wpcli_dev_seeded 2>$null)) {
    Write-Host '==> Seeding fake data'
    Invoke-Wp eval-file /opt/dev-seed/seed.php "--user=$($cfg.WP_ADMIN_USER)" | Out-Null
    Invoke-Wp db export $snapshot | Out-Null
}

# Seeding and installs can leave mail behind; start with an empty inbox.
Invoke-RestMethod -Method Delete -Uri "$mailUrl/api/v1/messages" | Out-Null

Write-Host ''
Write-Host "Site:     $siteUrl  (admin: $($cfg.WP_ADMIN_USER), password in dev\.env)"
Write-Host "Mail UI:  $mailUrl"
Write-Host "WP-CLI:   $(Invoke-Wp cli version)"
Write-Host "WordPress $(Invoke-Wp core version), WooCommerce $(Invoke-Wp plugin get woocommerce --field=version)"
Write-Host "Seed:     $(Invoke-Wp option get wpcli_dev_seed_facts --format=json)"
