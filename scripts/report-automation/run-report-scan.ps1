$ErrorActionPreference = 'Stop'

$wslDistribution = 'Ubuntu'
$projectPath = '/var/www/html/excel-extractor'

& wsl.exe -d $wslDistribution -- bash -lc "cd '$projectPath' && php artisan reports:scan --no-interaction"
$scanExitCode = $LASTEXITCODE

if ($scanExitCode -ne 0) {
    Write-Error "Report automation scan failed with exit code $scanExitCode."
}

exit $scanExitCode
