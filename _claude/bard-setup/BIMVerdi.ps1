[CmdletBinding()]
param(
    [ValidateSet('Status', 'Run', 'Wp')][string]$Action = 'Status',
    [string]$Command
)
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'Invoke-Ssh.ps1')
$config = Join-Path $PSScriptRoot 'ssh_config'
if (-not (Test-Path $config)) { throw 'Kjor Start-BIMVerdi.cmd forst.' }

if ($Action -eq 'Status') {
    $remote = @'
set -e
printf 'SSH_OK\n'
wp --path=/cust/0/bimver_15781/bimver_28287/home/site/public --skip-plugins --skip-themes core is-installed
printf 'DATABASE_OK\n'
test -r /cust/0/bimver_15781/bimver_28287/home/site/public/wp-content/themes/bimverdi-theme/functions.php
printf 'CODE_OK\n'
'@
} elseif ($Action -eq 'Wp') {
    if (-not $Command) { throw 'Oppgi en WP-CLI-kommando med -Command.' }
    $remote = 'wp --path=/cust/0/bimver_15781/bimver_28287/home/site/public ' + $Command
} else {
    if (-not $Command) { throw 'Oppgi en serverkommando med -Command.' }
    $remote = $Command
}
if ($Action -eq 'Status') {
    $result = Invoke-BIMVerdiSsh -ConfigPath $config -Alias bimverdi-prod -RemoteArguments @('sh', '-s') -InputText $remote
    $exitCode = $script:BIMVerdiSshExitCode
    $result | Write-Output
} else {
    Invoke-BIMVerdiSsh -ConfigPath $config -Alias bimverdi-prod -RemoteArguments @('sh', '-s') -InputText $remote
    $exitCode = $script:BIMVerdiSshExitCode
}
if ($exitCode -ne 0) { throw "Serverkommandoen feilet (kode $exitCode)." }
if ($Action -eq 'Status') {
    foreach ($marker in @('SSH_OK', 'DATABASE_OK', 'CODE_OK')) {
        if ($result -notcontains $marker) { throw "Testen mangler resultatet $marker." }
    }
    @{ ok = $true; checked_at = [DateTime]::UtcNow.ToString('o'); environment = 'production' } |
        ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $PSScriptRoot 'connection-status.json')
    Write-Host 'Tilgangen virker: SSH, WordPress-database og prosjektkode.' -ForegroundColor Green
}
