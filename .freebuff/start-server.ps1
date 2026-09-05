Start-Process -FilePath "node" -ArgumentList "build\preview\server.js" -WorkingDirectory "C:\Users\user\Desktop\Haro\DMX\Finance_ar Software" -WindowStyle Hidden
Start-Sleep -Seconds 3
$port = Get-NetTCPConnection -LocalPort 55400 -ErrorAction SilentlyContinue
if ($port) { Write-Host "SERVER_OK" } else { Write-Host "SERVER_FAIL" }
