$scriptPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\build\preview\server.js'
$logPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\.freebuff\preview-cb19a2d7-0df4-4d99-8a21-2ba6ea6423d5.log'
$errPath = Join-Path $env:USERPROFILE 'Desktop\Haro\DMX\Finance_ar Software\.freebuff\preview-cb19a2d7-0df4-4d99-8a21-2ba6ea6423d5.log.err'
Start-Process -FilePath 'node.exe' -ArgumentList $scriptPath -RedirectStandardOutput $logPath -RedirectStandardError $errPath -WindowStyle Hidden -PassThru
