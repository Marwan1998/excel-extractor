$ErrorActionPreference = 'Stop'

$taskName = 'Excel Extractor Report Automation'
$task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($null -eq $task) {
    Write-Output "Scheduled task is not installed: $taskName"
    exit 0
}

Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
Write-Output "Scheduled task removed: $taskName"
