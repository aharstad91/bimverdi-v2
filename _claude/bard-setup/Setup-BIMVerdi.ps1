[CmdletBinding()]
param()
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'Invoke-Ssh.ps1')
$workspace = Join-Path $env:LOCALAPPDATA 'BIMVerdi'
$sshDir = Join-Path $env:USERPROFILE '.ssh'
$keyPath = Join-Path $sshDir 'bimverdi_bard_ed25519'
$ssh = Join-Path $env:WINDIR 'System32\OpenSSH\ssh.exe'
$keygen = Join-Path $env:WINDIR 'System32\OpenSSH\ssh-keygen.exe'

function Protect-PrivateFile([string]$Path) {
    $sid = [System.Security.Principal.WindowsIdentity]::GetCurrent().User
    $acl = New-Object System.Security.AccessControl.FileSecurity
    $acl.SetOwner($sid)
    $acl.SetAccessRuleProtection($true, $false)
    $acl.AddAccessRule((New-Object System.Security.AccessControl.FileSystemAccessRule($sid, 'FullControl', 'Allow')))
    Set-Acl -LiteralPath $Path -AclObject $acl
}

function Invoke-Keygen([string]$Arguments) {
    $info = New-Object System.Diagnostics.ProcessStartInfo
    $info.FileName = $keygen
    $info.Arguments = $Arguments
    $info.UseShellExecute = $false
    $info.CreateNoWindow = $true
    $info.RedirectStandardOutput = $true
    $info.RedirectStandardError = $true
    $process = [System.Diagnostics.Process]::Start($info)
    $output = $process.StandardOutput.ReadToEnd()
    $errorOutput = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "Kunne ikke lage eller lese SSH-nokkelen: $errorOutput" }
    return $output.Trim()
}

function Invoke-Enrollment([string]$Action, [string]$PublicKey, [string]$Alias) {
    $json = @{ action = $Action; public_key = $PublicKey } | ConvertTo-Json -Compress
    if ($Alias -eq 'bimverdi-enroll') {
        $remoteArguments = @('enroll')
    } else {
        $remoteArguments = @('/usr/bin/python3', $manifest.remote_helper)
    }
    $output = Invoke-BIMVerdiSsh -ConfigPath (Join-Path $workspace 'ssh_config') -Alias $Alias -RemoteArguments $remoteArguments -InputText $json
    if ($script:BIMVerdiSshExitCode -ne 0) { throw "Registrering feilet: $output" }
    $response = ($output -join "`n") | ConvertFrom-Json
    if (-not $response.ok) { throw 'Serveren avviste registreringen.' }
}

try {
    Write-Host 'Setter opp BIM Verdi for Claude Code.'
    if (-not (Test-Path $ssh) -or -not (Test-Path $keygen)) {
        Write-Host 'Windows mangler OpenSSH Client. Godkjenn installasjonen hvis Windows spor.'
        $install = Start-Process powershell.exe -Verb RunAs -Wait -PassThru -ArgumentList @(
            '-NoProfile', '-Command', 'Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0 -ErrorAction Stop'
        )
        if ($install.ExitCode -ne 0 -or -not (Test-Path $ssh)) { throw 'Windows kunne ikke installere OpenSSH Client.' }
    }
    New-Item -ItemType Directory -Force -Path $workspace, $sshDir | Out-Null
    $manifest = Get-Content -Raw -LiteralPath (Join-Path $PSScriptRoot 'enrollment.json') | ConvertFrom-Json
    foreach ($name in @('BIMVerdi.ps1', 'Invoke-Ssh.ps1', 'CLAUDE.md', 'LES-MEG.txt', 'known_hosts')) {
        $source = Join-Path $PSScriptRoot $name
        $destination = Join-Path $workspace $name
        if ([IO.Path]::GetFullPath($source) -ne [IO.Path]::GetFullPath($destination)) {
            Copy-Item -Force -LiteralPath $source -Destination $destination
        }
    }
    $contextSource = Join-Path $PSScriptRoot 'kontekst'
    if (Test-Path $contextSource) {
        Copy-Item -Force -Recurse -LiteralPath $contextSource -Destination $workspace
    }
    Set-Content -Encoding ASCII -LiteralPath (Join-Path $workspace '.gitignore') -Value '*'
    if (-not (Test-Path $keyPath)) {
        Write-Host 'Lager din egen SSH-nokkel pa denne PC-en.'
        Invoke-Keygen ('-q -t ed25519 -N "" -C bimverdi-bard -f "' + $keyPath + '"') | Out-Null
    }
    Protect-PrivateFile $keyPath
    $publicKey = Invoke-Keygen ('-y -P "" -f "' + $keyPath + '"')
    Set-Content -Encoding ASCII -LiteralPath ($keyPath + '.pub') -Value $publicKey
    $bootstrap = Join-Path $workspace '.enrollment_key'
    $knownHosts = (Join-Path $workspace 'known_hosts').Replace('\', '/')
    $permanent = $keyPath.Replace('\', '/')
    $temporary = $bootstrap.Replace('\', '/')
    $config = @"
Host bimverdi-prod
    IdentityFile "$permanent"
Host bimverdi-enroll
    IdentityFile "$temporary"
Host bimverdi-prod bimverdi-enroll
    HostName jana-osl.servebolt.cloud
    User bimver_28287
    Port 22
    IdentitiesOnly yes
    BatchMode yes
    ConnectTimeout 15
    StrictHostKeyChecking yes
    HostKeyAlgorithms ssh-ed25519
    UserKnownHostsFile "$knownHosts"
    GlobalKnownHostsFile none
"@
    [IO.File]::WriteAllText((Join-Path $workspace 'ssh_config'), $config, (New-Object Text.UTF8Encoding($false)))
    Invoke-BIMVerdiSsh -ConfigPath (Join-Path $workspace 'ssh_config') -Alias bimverdi-prod -RemoteArguments @('sh', '-s') -InputText 'true' -Quiet
    $alreadyConnected = $script:BIMVerdiSshExitCode -eq 0
    $sourceKey = Join-Path $PSScriptRoot '.enrollment_key'
    $expired = [DateTimeOffset]::UtcNow -ge [DateTimeOffset]::Parse($manifest.expires_at)
    $needsEnrollment = (Test-Path $sourceKey) -and -not $expired
    if (-not $alreadyConnected -and -not $needsEnrollment) {
        throw 'Engangstilgangen mangler eller har utlopt. Be Andreas lage en ny pakke.'
    }
    if ($needsEnrollment) {
        Protect-PrivateFile $sourceKey
        if ([IO.Path]::GetFullPath($sourceKey) -ne [IO.Path]::GetFullPath($bootstrap)) {
            Copy-Item -Force -LiteralPath $sourceKey -Destination $bootstrap
        }
        Protect-PrivateFile $bootstrap
        Write-Host 'Registrerer SSH-nokkelen og tester forbindelsen.'
        if ($alreadyConnected) { Invoke-Enrollment 'enroll' $publicKey 'bimverdi-prod' }
        else { Invoke-Enrollment 'enroll' $publicKey 'bimverdi-enroll' }
    }
    & (Join-Path $workspace 'BIMVerdi.ps1') -Action Status
    if ($needsEnrollment) {
        Invoke-Enrollment 'finalize' $publicKey 'bimverdi-prod'
    }
    foreach ($path in @($bootstrap, (Join-Path $PSScriptRoot '.enrollment_key'))) {
        if (Test-Path $path) { Remove-Item -Force -LiteralPath $path }
    }
    $desktop = [Environment]::GetFolderPath('Desktop')
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut((Join-Path $desktop 'BIM Verdi.lnk'))
    $shortcut.TargetPath = $workspace
    $shortcut.Save()
    Set-Clipboard -Value 'Les CLAUDE.md. Sjekk tilgangen til BIM Verdi og forklar hva du kan hjelpe meg med.'
    Write-Host ''
    Write-Host 'Ferdig. Apne Code i Claude og velg denne mappen:' -ForegroundColor Green
    Write-Host $workspace
    Write-Host 'Lim inn teksten som oppsettet har kopiert til utklippstavlen.'
    Start-Process explorer.exe -ArgumentList ('"' + $workspace + '"')
} catch {
    Write-Host ''
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
