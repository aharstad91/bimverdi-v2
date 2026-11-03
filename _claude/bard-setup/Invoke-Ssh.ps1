function Invoke-BIMVerdiSsh {
    param(
        [string]$ConfigPath,
        [string]$Alias,
        [string[]]$RemoteArguments,
        [string]$InputText,
        [switch]$Quiet
    )
    $sshPath = Join-Path $env:WINDIR 'System32\OpenSSH\ssh.exe'
    if (-not (Test-Path $sshPath)) { $sshPath = (Get-Command ssh.exe -ErrorAction Stop).Source }
    $nativeArguments = @('-F', $ConfigPath, $Alias) + $RemoteArguments
    $quoted = foreach ($argument in $nativeArguments) {
        $escaped = [regex]::Replace($argument, '(\\*)"', '$1$1\"')
        '"' + [regex]::Replace($escaped, '(\\+)$', '$1$1') + '"'
    }
    $info = New-Object System.Diagnostics.ProcessStartInfo
    $info.FileName = $sshPath
    $info.Arguments = $quoted -join ' '
    $info.UseShellExecute = $false
    $info.CreateNoWindow = $true
    $info.RedirectStandardInput = $true
    $info.RedirectStandardOutput = $true
    $info.RedirectStandardError = $true
    $info.StandardOutputEncoding = New-Object Text.UTF8Encoding($false)
    $info.StandardErrorEncoding = New-Object Text.UTF8Encoding($false)
    $process = [System.Diagnostics.Process]::Start($info)
    try {
        $errors = $process.StandardError.ReadToEndAsync()
        # Windows PowerShell adds CRLF to native pipelines; write the protocol bytes directly.
        $inputWithLinuxNewlines = $InputText.Replace("`r`n", "`n") + "`n"
        $bytes = (New-Object Text.UTF8Encoding($false)).GetBytes($inputWithLinuxNewlines)
        $process.StandardInput.BaseStream.Write($bytes, 0, $bytes.Length)
        $process.StandardInput.BaseStream.Close()
        while (-not $process.StandardOutput.EndOfStream) {
            $line = $process.StandardOutput.ReadLine()
            if (-not $Quiet) { Write-Output $line }
        }
        $process.WaitForExit()
        $script:BIMVerdiSshExitCode = $process.ExitCode
        $errorText = $errors.GetAwaiter().GetResult()
        if (-not $Quiet -and $errorText) { [Console]::Error.Write($errorText) }
    } finally {
        $process.Dispose()
    }
}
