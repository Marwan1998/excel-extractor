# Report folder automation

The automation scans drilling and workover report trees, routes each supported file to its company pipeline, and records processing state without a database.

It is disabled by default. Do not enable it until the folder roots and first-run policy have been checked with a dry run.

## Folder layout

Both configured roots use this structure:

```text
<report root>/<company>/<year>/<month>/<report file>
```

Supported company folders are `AGOCO`, `AOO`, `SOC`, and `WOC`. `WAHA` is accepted as an alias for `WOC`.

## Configure the roots

Add the following values to `.env`. Because the command runs inside WSL, use `/mnt/c/...` paths rather than `C:\...` paths.

```dotenv
REPORT_AUTOMATION_ENABLED=false
REPORT_AUTOMATION_DRILLING_ROOT="/mnt/c/path/to/Drilling Reports Per Company"
REPORT_AUTOMATION_WORKOVER_ROOT="/mnt/c/path/to/Workover Reports Per Company"
REPORT_AUTOMATION_STABILITY_SECONDS=120
REPORT_AUTOMATION_MAXIMUM_ATTEMPTS=3
REPORT_AUTOMATION_MAXIMUM_BACKUPS=20
```

Then clear cached configuration:

```bash
php artisan config:clear
```

## Verify discovery safely

This command discovers and displays files but does not run an extractor, update the JSON ledger, or change DDR/DWR:

```bash
php artisan reports:scan --force --dry-run
```

## Choose the first-run policy

To ignore all reports that already exist and process only future additions:

```bash
php artisan reports:baseline --confirm
```

To import the existing history, do not baseline. Enable automation and run `reports:scan`. Historical imports should be done in small batches first:

```bash
php artisan reports:scan --force --limit=5
```

## Enable processing

Set this value in `.env` and clear the configuration cache:

```dotenv
REPORT_AUTOMATION_ENABLED=true
```

```bash
php artisan config:clear
php artisan reports:scan
```

## Inspect processing status

```bash
php artisan reports:status
php artisan reports:status --status=failed
php artisan reports:status --status=empty
```

State is stored under `storage/app/report-automation/`:

- `status.json`: current status for every fingerprinted report.
- `events.log`: append-only JSON-lines operational history.
- `backups/`: recent DDR/DWR backups.
- `staging/` and `working/`: temporary processing files.
- `locks/`: scan and workbook locks.

An `empty` result is recorded and not retried unless the source file changes. A failed file is retried up to the configured attempt limit.

## Install the Windows scheduled task

From PowerShell, run this once:

```powershell
powershell.exe -ExecutionPolicy Bypass -File "\\wsl.localhost\Ubuntu\var\www\html\excel-extractor\scripts\report-automation\install-task.ps1"
```

The installed task runs 10 minutes after Windows starts, then daily at 09:30, 10:00, 10:30, 11:00, 11:30, 12:00, 12:30, 13:00, 14:00, and 15:00. Missed runs start when possible, and overlapping runs are ignored.

To remove it:

```powershell
powershell.exe -ExecutionPolicy Bypass -File "\\wsl.localhost\Ubuntu\var\www\html\excel-extractor\scripts\report-automation\uninstall-task.ps1"
```

## Operational notes

- Mark the OneDrive report roots as **Always keep on this device**.
- Files younger than the stability window are left in `waiting` status.
- Reports are copied into staging; originals are never moved or deleted.
- Dumps write to a working workbook copy. DDR/DWR is backed up and replaced only after a successful extraction and matching row count.
- If DDR/DWR is locked by Excel, processing is recorded as failed and retried later.
- Keep automation disabled while changing paths or rebuilding the workbooks.
