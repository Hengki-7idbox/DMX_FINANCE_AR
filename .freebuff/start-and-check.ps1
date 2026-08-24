$scriptPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\build\preview\server.js'
$logPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\.freebuff\preview-cb19a2d7-0df4-4d99-8a21-2ba6ea6423d5.log'
$errPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\.freebuff\preview-cb19a2d7-0df4-4d99-8a21-2ba6ea6423d5.log.err'

# Kill existing node processes on port
$existing = Get-NetTCPConnection -LocalPort 55400 -ErrorAction SilentlyContinue
if ($existing) {
    Stop-Process -Id $existing.OwningProcess -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
}

$quotedPath = "`"$scriptPath`""
$p = Start-Process -FilePath 'node.exe' -ArgumentList $quotedPath -RedirectStandardOutput $logPath -RedirectStandardError $errPath -WindowStyle Hidden -PassThru
Write-Host ("PID=" + $p.Id)
Start-Sleep -Seconds 3
$alive = Get-Process -Id $p.Id -ErrorAction SilentlyContinue
if ($alive) { Write-Host "ALIVE" } else { Write-Host "DEAD" }
