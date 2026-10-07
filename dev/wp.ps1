# Runs WP-CLI inside the dev sandbox:  dev\wp.ps1 wc product list --user=1 --format=json
# Arguments are passed through untouched. Quote JSON and values with spaces in single quotes.
$PSNativeCommandArgumentPassing = 'Standard'

# PowerShell turns an unquoted a,b,c into an array; put it back together.
$wpArgs = foreach ($a in $args) { if ($a -is [array]) { $a -join ',' } else { "$a" } }

$dockerArgs = @('exec', '-i')
if ($env:WPCLI_SCENARIO) { $dockerArgs += @('-e', "WPCLI_SCENARIO=$env:WPCLI_SCENARIO") }
$dockerArgs += @('wpcli-cli', 'wp') + @($wpArgs)

if ($MyInvocation.ExpectingInput) { $input | & docker @dockerArgs } else { & docker @dockerArgs }
exit $LASTEXITCODE
