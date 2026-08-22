# restore.ps1 - Restaura um backup gerado pelo backup.ps1
#
# USO:
#   .\restore.ps1 -ZipFile "C:\BKP-MAPOS\mapos_backup_2026-08-21_23-30.zip"
#
# ATENCAO: isso SUBSTITUI o banco de dados e os uploads atuais pelos do backup.
# So rode isso se tiver certeza (ex: trocando de PC, ou recuperando de um problema).

param(
    [Parameter(Mandatory = $true)]
    [string]$ZipFile
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ZipFile)) {
    throw "Arquivo de backup nao encontrado: $ZipFile"
}

$DbContainer = "mysql"
$DbUser      = "root"
$DbPass      = "root"     # <-- mesma senha usada no backup.ps1 / docker/.env
$DbName      = "mapos"

$DockerDir = $PSScriptRoot | Split-Path -Parent
$AppRoot   = $DockerDir | Split-Path -Parent

$TmpDir = Join-Path $env:TEMP "mapos_restore_$(Get-Date -Format 'yyyyMMddHHmmss')"
New-Item -ItemType Directory -Path $TmpDir -Force | Out-Null

Write-Host "Descompactando backup..."
Expand-Archive -Path $ZipFile -DestinationPath $TmpDir -Force

$confirm = Read-Host "Isso vai SUBSTITUIR o banco de dados atual do MAPOS pelo do backup. Digite CONFIRMAR para continuar"
if ($confirm -ne "CONFIRMAR") {
    Write-Host "Cancelado."
    Remove-Item -Path $TmpDir -Recurse -Force
    exit 1
}

Write-Host "[1/3] Restaurando banco de dados..."
$DumpFile = Join-Path $TmpDir "mapos_db.sql"
Get-Content $DumpFile | docker exec -i $DbContainer sh -c "exec mysql -u$DbUser -p$DbPass $DbName"

Write-Host "[2/3] Restaurando uploads e fotos de usuario..."
$UploadsSrc = Join-Path $TmpDir "uploads"
$UserImgSrc = Join-Path $TmpDir "userImage"
if (Test-Path $UploadsSrc) {
    Remove-Item -Path (Join-Path $AppRoot "assets\uploads") -Recurse -Force -ErrorAction SilentlyContinue
    Copy-Item -Path $UploadsSrc -Destination (Join-Path $AppRoot "assets\uploads") -Recurse -Force
}
if (Test-Path $UserImgSrc) {
    Remove-Item -Path (Join-Path $AppRoot "assets\userImage") -Recurse -Force -ErrorAction SilentlyContinue
    Copy-Item -Path $UserImgSrc -Destination (Join-Path $AppRoot "assets\userImage") -Recurse -Force
}

Write-Host "[3/3] O .env NAO foi restaurado automaticamente (para nao sobrescrever configs do PC atual)."
Write-Host "Se precisar dele, esta em: $(Join-Path $TmpDir '.env')"

Remove-Item -Path $TmpDir -Recurse -Force
Write-Host ""
Write-Host "Restauracao concluida." -ForegroundColor Green
