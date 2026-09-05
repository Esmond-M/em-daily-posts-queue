param(
    [Parameter(Mandatory = $true)]
    [string]$MySqlBinDirectory,
    [string]$PhpBinary = 'php',
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PhpUnitArguments = @()
)

# Start a disposable MySQL 8 instance; never connect to a Local site instance.
$ErrorActionPreference = 'Stop'
$edpqRoot = Split-Path $PSScriptRoot -Parent
$edpqMysqlBin = (Resolve-Path -LiteralPath $MySqlBinDirectory).Path
$edpqPhp = (Get-Command $PhpBinary -ErrorAction Stop).Source
$edpqServerExe = Join-Path $edpqMysqlBin 'mysqld.exe'
$edpqClientExe = Join-Path $edpqMysqlBin 'mysql.exe'
$edpqAdminExe = Join-Path $edpqMysqlBin 'mysqladmin.exe'
foreach ($edpqBinary in @($edpqServerExe, $edpqClientExe, $edpqAdminExe)) {
    if (!(Test-Path -LiteralPath $edpqBinary -PathType Leaf)) { throw "Missing executable: $edpqBinary" }
}
$edpqServerVersion = & $edpqServerExe --version
$edpqSupportsMysqlx = $edpqServerVersion -match 'Ver\s+([8-9]|[1-9][0-9])\.'

$edpqToken = [Guid]::NewGuid().ToString('N')
$edpqTempBase = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$edpqTemp = Join-Path $edpqTempBase "edpq-integration-$edpqToken"
$edpqData = Join-Path $edpqTemp 'data'
$edpqDatabase = 'edpq_tests_' + $edpqToken.Substring(0, 12)
$edpqPassword = [Guid]::NewGuid().ToString('N')
$edpqSavedEnv = @{}
$edpqServer = $null
$edpqExit = 1
foreach ($edpqName in @('EDPQ_RUN_INTEGRATION', 'EDPQ_TEST_DB_NAME', 'EDPQ_TEST_DB_HOST', 'EDPQ_TEST_DB_PASSWORD', 'MYSQL_PWD')) {
    $edpqSavedEnv[$edpqName] = [Environment]::GetEnvironmentVariable($edpqName, 'Process')
}

try {
    New-Item -ItemType Directory -Path $edpqData -Force | Out-Null
    $edpqInit = Start-Process -FilePath $edpqServerExe -ArgumentList @(
        '--no-defaults', '--initialize-insecure', "`"--datadir=$edpqData`"", '--console'
    ) -WindowStyle Hidden -PassThru -Wait -RedirectStandardOutput (Join-Path $edpqTemp 'init.out') -RedirectStandardError (Join-Path $edpqTemp 'init.err')
    if ($edpqInit.ExitCode -ne 0) { throw "MySQL initialization failed; see $edpqTemp" }

    $edpqListener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, 0)
    $edpqListener.Start()
    $edpqPort = $edpqListener.LocalEndpoint.Port
    $edpqListener.Stop()
    $edpqInitFile = Join-Path $edpqTemp 'init.sql'
    [System.IO.File]::WriteAllText($edpqInitFile, "ALTER USER 'root'@'localhost' IDENTIFIED BY '$edpqPassword';`nCREATE DATABASE $edpqDatabase;`n")
    $edpqServerArgs = @(
        '--no-defaults', "`"--datadir=$edpqData`"", '--bind-address=127.0.0.1', "--port=$edpqPort",
        "`"--init-file=$edpqInitFile`"", '--console'
    )
    if ($edpqSupportsMysqlx) { $edpqServerArgs += '--mysqlx=OFF' }
    $edpqServer = Start-Process -FilePath $edpqServerExe -ArgumentList $edpqServerArgs -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $edpqTemp 'server.out') -RedirectStandardError (Join-Path $edpqTemp 'server.err')
    $env:MYSQL_PWD = $edpqPassword
    $edpqConnection = @('--no-defaults', '--host=127.0.0.1', "--port=$edpqPort", '--user=root')
    $edpqReady = $false
    for ($edpqAttempt = 0; $edpqAttempt -lt 60; $edpqAttempt++) {
        if ($edpqServer.HasExited) { throw "Test MySQL exited; see $edpqTemp" }
        # TCP readiness avoids printing expected connection/auth errors during startup.
        $edpqProbe = [System.Net.Sockets.TcpClient]::new()
        try { $edpqProbe.Connect('127.0.0.1', $edpqPort); $edpqReady = $true } catch { } finally { $edpqProbe.Dispose() }
        if ($edpqReady) { break }
        Start-Sleep -Milliseconds 500
    }
    if (!$edpqReady) { throw "Test MySQL did not start; see $edpqTemp" }
    & $edpqClientExe @edpqConnection --execute="SELECT 1" --batch --skip-column-names | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Test database connection failed.' }

    $env:EDPQ_RUN_INTEGRATION = '1'
    $env:EDPQ_TEST_DB_NAME = $edpqDatabase
    $env:EDPQ_TEST_DB_HOST = "127.0.0.1:$edpqPort"
    $env:EDPQ_TEST_DB_PASSWORD = $edpqPassword
    Push-Location $edpqRoot
    try {
        & $edpqPhp vendor/bin/phpunit --configuration phpunit.integration.xml @PhpUnitArguments
        $edpqExit = $LASTEXITCODE
    } finally { Pop-Location }
} finally {
    if ($null -ne $edpqServer -and !$edpqServer.HasExited) {
        & $edpqAdminExe @edpqConnection shutdown
        if (!$edpqServer.WaitForExit(10000)) { $edpqServer.Kill(); $edpqServer.WaitForExit() }
    }
    foreach ($edpqName in $edpqSavedEnv.Keys) {
        [Environment]::SetEnvironmentVariable($edpqName, $edpqSavedEnv[$edpqName], 'Process')
    }
    # Delete only this run's validated temporary directory, after its server stops.
    $edpqResolved = [System.IO.Path]::GetFullPath($edpqTemp)
    if ($edpqExit -eq 0 -and $edpqResolved.StartsWith($edpqTempBase, [StringComparison]::OrdinalIgnoreCase) -and (Split-Path $edpqResolved -Leaf) -eq "edpq-integration-$edpqToken") {
        Remove-Item -LiteralPath $edpqResolved -Recurse -Force
    } elseif (Test-Path -LiteralPath $edpqTemp) {
        Write-Output "Failed-run logs retained in $edpqTemp"
    }
}
exit $edpqExit
