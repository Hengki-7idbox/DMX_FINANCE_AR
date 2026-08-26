$dir = 'C:\Users\user\Desktop\Haro\DMX\Finance_ar Software'
$log = 'C:\Users\user\Desktop\Haro\DMX\Finance_ar Software\.freebuff\preview-server.log'
Start-Process -FilePath 'node.exe' -ArgumentList "`"$dir\build\preview\server.js`"" -RedirectStandardOutput $log -RedirectStandardError "$log.err" -WindowStyle Hidden -PassThru
Start-Sleep -Seconds 2
Write-Host "Server started"
