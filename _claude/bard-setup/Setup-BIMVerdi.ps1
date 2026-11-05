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
    # icacls, not Set-Acl: Set-Acl also writes the audit section, which needs SeSecurityPrivilege (admin only).
    # Removes inherited access and leaves full control for the current user only. SID form works in every Windows language.
    $sid = [System.Security.Principal.WindowsIdentity]::GetCurrent().User.Value
    $icacls = Join-Path $env:WINDIR 'System32\icacls.exe'
    $output = & $icacls $Path /inheritance:r /grant:r ('*' + $sid + ':F')
    if ($LASTEXITCODE -ne 0) { throw "Kunne ikke låse tilgangen til ${Path}: $output" }
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

function Invoke-Enrollment([hashtable]$Request) {
    # Always through the one-time key: its forced command is enroll.py. The permanent key only reaches the gate.
    $json = $Request | ConvertTo-Json -Compress
    # No -Quiet here: the JSON answer arrives on stdout and must be captured.
    $output = Invoke-BIMVerdiSsh -ConfigPath (Join-Path $workspace 'ssh_config') -Alias bimverdi-enroll -RemoteArguments @($Request.action) -InputText $json
    $text = ($output -join "`n").Trim()
    if (-not $text) { throw "Serveren svarte ikke (kode $script:BIMVerdiSshExitCode). Er engangstilgangen aktivert?" }
    try { $response = $text | ConvertFrom-Json } catch { throw "Uventet svar fra serveren: $text" }
    if (-not $response.ok) { throw "Serveren avviste forespørselen: $($response.error)" }
    return $response
}

try {
    Write-Host 'Setter opp BIM Verdi for Claude Code.'
    if (-not (Test-Path $ssh) -or -not (Test-Path $keygen)) {
        Write-Host 'Windows mangler OpenSSH Client. Godkjenn installasjonen hvis Windows spør.'
        $install = Start-Process powershell.exe -Verb RunAs -Wait -PassThru -ArgumentList @(
            '-NoProfile', '-Command', 'Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0 -ErrorAction Stop'
        )
        if ($install.ExitCode -ne 0 -or -not (Test-Path $ssh)) { throw 'Windows kunne ikke installere OpenSSH Client.' }
    }
    New-Item -ItemType Directory -Force -Path $workspace, $sshDir, (Join-Path $workspace '.claude') | Out-Null
    $manifest = Get-Content -Raw -LiteralPath (Join-Path $PSScriptRoot 'enrollment.json') | ConvertFrom-Json
    foreach ($name in @('BIMVerdi.ps1', 'Invoke-Ssh.ps1', 'CLAUDE.md', 'LES-MEG.txt', 'known_hosts', '.claude\settings.json')) {
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
        Write-Host 'Lager din egen SSH-nøkkel på denne PC-en.'
        Invoke-Keygen ('-q -t ed25519 -N "" -C bimverdi-bard -f "' + $keyPath + '"') | Out-Null
    }
    Protect-PrivateFile $keyPath
    # Windows ssh-keygen -y adds the comment; the server accepts only "ssh-ed25519 <blob>".
    $publicKey = ((Invoke-Keygen ('-y -P "" -f "' + $keyPath + '"')) -split '\s+')[0..1] -join ' '
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
    $sourceKey = Join-Path $PSScriptRoot '.enrollment_key'
    if ((Test-Path $sourceKey) -and ([IO.Path]::GetFullPath($sourceKey) -ne [IO.Path]::GetFullPath($bootstrap))) {
        Protect-PrivateFile $sourceKey
        Copy-Item -Force -LiteralPath $sourceKey -Destination $bootstrap
    }
    if (Test-Path $bootstrap) { Protect-PrivateFile $bootstrap }
    $bootstrapPresent = Test-Path $bootstrap
    Invoke-BIMVerdiSsh -ConfigPath (Join-Path $workspace 'ssh_config') -Alias bimverdi-prod -RemoteArguments @('sh', '-s') -InputText 'true' -Quiet
    $alreadyConnected = $script:BIMVerdiSshExitCode -eq 0
    $needsEnrollment = (-not $alreadyConnected) -and $bootstrapPresent
    if (-not $alreadyConnected -and -not $needsEnrollment) {
        throw 'Engangstilgangen mangler. Be Andreas om en ny pakke.'
    }
    if ($needsEnrollment) {
        $ping = Invoke-Enrollment @{ action = 'ping' }
        if ($ping.revoked) { throw 'Denne pakken er trukket tilbake. Be Andreas om en ny.' }
        if ($ping.expired) { throw 'Engangstilgangen har utløpt. Be Andreas aktivere pakken på nytt.' }
        $expires = ([DateTimeOffset]::Parse($ping.expires_at)).ToLocalTime().ToString('dd.MM.yyyy HH:mm')
        Write-Host ''
        Write-Host "Pakken $($ping.id) er aktiv til $expires." -ForegroundColor Cyan
        Write-Host ''
        Write-Host 'ANSVAR' -ForegroundColor Yellow
        Write-Host 'Denne tilgangen går rett til produksjonsnettstedet bimverdi.no.'
        Write-Host 'Endringer virker med en gang for alle besøkende.'
        Write-Host 'Du bruker tilgangen på eget ansvar. Andreas godkjenner ikke endringene dine på forhånd.'
        Write-Host 'Alle kommandoer på serveren logges med fullt innhold.'
        Write-Host 'Serveren sperrer noen få farlige kommandoer og tar databasekopi før endringer i databasen.'
        Write-Host 'Det er et vern mot uhell, ikke en garanti. Et uhell kan slette innhold, lekke data'
        Write-Host 'eller kreve gjenoppretting der nyere endringer går tapt.'
        Write-Host ''
        $answer = Read-Host 'Skriv JA for å bekrefte at du har lest og godtar dette'
        if ($answer.Trim().ToUpperInvariant() -ne 'JA') { throw 'Oppsettet er avbrutt. Ingen tilgang er opprettet.' }
        $code = Read-Host 'Engangskode fra Andreas (6 siffer)'
        Write-Host 'Registrerer SSH-nøkkelen din på serveren.'
        $enrolled = Invoke-Enrollment @{
            action = 'enroll'; public_key = $publicKey; code = $code.Trim()
            accepted_responsibility = $true; client_name = ($env:COMPUTERNAME + '\' + $env:USERNAME)
        }
        Write-Host "Nøkkelen er registrert. Fingeravtrykk: $($enrolled.fingerprint)" -ForegroundColor Green
    }
    & (Join-Path $workspace 'BIMVerdi.ps1') -Action Status
    if ($bootstrapPresent) {
        # Also on a re-run after an interrupted first run: finalize is idempotent and bound to this key.
        try {
            Invoke-Enrollment @{ action = 'finalize'; public_key = $publicKey } | Out-Null
            Write-Host 'Engangstilgangen er slettet på serveren. Bare din egen nøkkel gjelder nå.'
        } catch {
            Write-Host ('Engangstilgangen kunne ikke ryddes nå: ' + $_.Exception.Message) -ForegroundColor Yellow
            Write-Host 'Den utløper av seg selv. Din egen nøkkel virker.' -ForegroundColor Yellow
        }
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
    Write-Host 'Ferdig. Åpne Code i Claude og velg denne mappen:' -ForegroundColor Green
    Write-Host $workspace
    Write-Host 'Lim inn teksten som oppsettet har kopiert til utklippstavlen.'
    Start-Process explorer.exe -ArgumentList ('"' + $workspace + '"')
} catch {
    Write-Host ''
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
