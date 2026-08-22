# backup.ps1 - Backup completo do MAPOS (banco de dados + uploads + configuracao)
#
# O que faz:
#   1. Gera um dump do MySQL (dentro do container "mysql")
#   2. Copia a pasta de anexos/uploads e as fotos de usuario
#   3. Copia o application/.env (config + chaves)
#   4. Compacta tudo num unico .zip com data/hora
#   5. Apaga backups .zip mais antigos que $KeepDays
#
# Requisitos: Docker Desktop aberto e os containers do MAPOS rodando.
#
# Como agendar (rodar sozinho todo dia):
#   1. Abra "Agendador de Tarefas" (Task Scheduler) no Windows
#   2. Criar Tarefa Basica > Diariamente > horario (ex: 23:30, fora do expediente)
#   3. Acao: "Iniciar um programa"
#      Programa/script: powershell.exe
#      Argumentos:      -ExecutionPolicy Bypass -File "CAMINHO_COMPLETO\backup.ps1"
#   4. Marque "Executar mesmo que o usuario nao esteja conectado" (precisa de senha do Windows)

$ErrorActionPreference = "Stop"

# ============================================================
# CONFIGURACAO - ajuste antes do primeiro uso
# ============================================================

# Pasta onde os .zip de backup vao ficar.
# DICA: aponte para uma pasta sincronizada (OneDrive/Google Drive) para ter
# copia fora do PC automaticamente, sem esforco extra.
$BackupDir = "C:\BKP-MAPOS"

# Quantos dias de backup manter localmente (os mais antigos sao apagados)
$KeepDays = 30

# Dados de conexao do MySQL (devem bater com docker/.env)
$DbContainer = "mysql"
$DbUser      = "root"
$DbPass      = "root"     # <-- TROQUE aqui se voce mudar a senha no docker/.env
$DbName      = "mapos"

# ============================================================
# NAO PRECISA MEXER DAQUI PRA BAIXO
# ============================================================

$DockerDir = $PSScriptRoot | Split-Path -Parent          # .../docker
$AppRoot   = $DockerDir | Split-Path -Parent               # raiz do projeto

$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm"
$WorkDir   = Join-Path $BackupDir "tmp_$Timestamp"
New-Item -ItemType Directory -Path $WorkDir -Force | Out-Null

try {
    Write-Host "[1/4] Gerando dump do banco de dados..."
    $DumpFile = Join-Path $WorkDir "mapos_db.sql"
    docker exec $DbContainer sh -c "exec mysqldump -u$DbUser -p$DbPass $DbName" 2>$null | Out-File -Encoding utf8 -FilePath $DumpFile

    if (-not (Test-Path $DumpFile) -or (Get-Item $DumpFile).Length -eq 0) {
        throw "O dump do banco ficou vazio. Confira se o container '$DbContainer' esta rodando e a senha em `$DbPass esta certa."
    }

    Write-Host "[2/4] Copiando uploads, anexos e fotos de usuario..."
    $UploadsSrc = Join-Path $AppRoot "assets\uploads"
    $UserImgSrc = Join-Path $AppRoot "assets\userImage"
    $EnvSrc     = Join-Path $AppRoot "application\.env"

    if (Test-Path $UploadsSrc) { Copy-Item -Path $UploadsSrc -Destination (Join-Path $WorkDir "uploads") -Recurse -Force }
    if (Test-Path $UserImgSrc) { Copy-Item -Path $UserImgSrc -Destination (Join-Path $WorkDir "userImage") -Recurse -Force }
    if (Test-Path $EnvSrc)     { Copy-Item -Path $EnvSrc -Destination (Join-Path $WorkDir ".env") -Force }

    Write-Host "[3/4] Compactando..."
    $ZipFile = Join-Path $BackupDir "mapos_backup_$Timestamp.zip"
    Compress-Archive -Path (Join-Path $WorkDir "*") -DestinationPath $ZipFile -Force

    Write-Host "[4/4] Limpando backups com mais de $KeepDays dias..."
    Get-ChildItem -Path $BackupDir -Filter "mapos_backup_*.zip" |
        Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } |
        Remove-Item -Force

    Write-Host ""
    Write-Host "Backup concluido com sucesso: $ZipFile" -ForegroundColor Green
}
finally {
    if (Test-Path $WorkDir) { Remove-Item -Path $WorkDir -Recurse -Force }
}
