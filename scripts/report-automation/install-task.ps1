$ErrorActionPreference = 'Stop'

$taskName = 'Excel Extractor Report Automation'
$runnerPath = Join-Path $PSScriptRoot 'run-report-scan.ps1'

if (-not (Test-Path -LiteralPath $runnerPath)) {
    throw "The report automation runner was not found: $runnerPath"
}

$actionArguments = "-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$runnerPath`""
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $actionArguments
$startupTrigger = New-ScheduledTaskTrigger -AtStartup
$startupTrigger.Delay = 'PT10M'

$dailyTimes = @(
    '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '14:00', '15:00'
)

$dailyTriggers = $dailyTimes | ForEach-Object {
    New-ScheduledTaskTrigger -Daily -At ([datetime]::ParseExact($_, 'HH:mm', [Globalization.CultureInfo]::InvariantCulture))
}

$triggers = @($startupTrigger) + @($dailyTriggers)
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $triggers `
    -Settings $settings `
    -Description 'Scans drilling and workover report folders and updates DDR/DWR.' `
    -Force

Write-Output "Scheduled task installed: $taskName"
Write-Output 'The task runs 10 minutes after startup and at the configured daily times.'
Write-Output 'Overlapping runs are ignored.'
