# start-mapos.ps1 - Sobe o MAPOS automaticamente quando o PC liga.
#
# O que faz, nessa ordem:
#   1. Espera o Docker Desktop terminar de iniciar (pode levar 1-2 min no boot)
#   2. Sobe os containers (docker compose up -d) - seguro rodar mesmo se ja estiverem no ar
#   3. Se ainda nao existe um backup de HOJE, roda o backup.ps1
#      (cobre o fechamento do dia anterior, ja que o PC nao fica ligado 24/7)
#   4. Abre o navegador direto na tela de login do sistema
#
# Pensado pra rodar sozinho via Agendador de Tarefas, disparado no logon do Windows
# (que por sua vez precisa estar configurado com login automatico - veja README.md).
#
# Tudo que acontece fica registrado em start-mapos.log, na mesma pasta deste script,
# util pra descobrir o que houve caso algo nao suba certo.

$ErrorActionPreference = "Stop"

# ============================================================
# CONFIGURACAO - ajuste antes do primeiro uso
# ============================================================

# URL local do sistema (o IP fixo reservado no roteador pra este PC)
$AppUrl = "http://localhost:8000"

# Quanto tempo esperar o Docker ficar pronto antes de desistir (segundos)
$DockerTimeoutSeconds = 180

# ============================================================
# NAO PRECISA MEXER DAQUI PRA BAIXO
# ============================================================

$ScriptDir = $PSScriptRoot
$DockerDir = $ScriptDir | Split-Path -Parent
$LogFile   = Join-Path $ScriptDir "start-mapos.log"

function Log($msg) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $msg"
    Add-Content -Path $LogFile -Value $line
    Write-Host $line
}

Log "===== Iniciando start-mapos.ps1 ====="

# --- 1. Espera o Docker ficar pronto ---
Log "Aguardando o Docker Desktop ficar pronto..."
$elapsed = 0
$ready = $false
while ($elapsed -lt $DockerTimeoutSeconds) {
    docker info *> $null
    if ($LASTEXITCODE -eq 0) {
        $ready = $true
        break
    }
    Start-Sleep -Seconds 5
    $elapsed += 5
}

if (-not $ready) {
    Log "ERRO: Docker nao ficou pronto depois de $DockerTimeoutSeconds segundos. Abortando."
    exit 1
}
Log "Docker pronto (levou ~$elapsed segundos)."

# --- 2. Sobe os containers ---
Log "Subindo os containers (docker compose up -d)..."
Push-Location $DockerDir
docker compose up -d *>> $LogFile
Pop-Location
Log "Containers no ar."

# --- 3. Backup do dia, se ainda nao foi feito hoje ---
# IMPORTANTE: se voce mudar $BackupDir no backup.ps1 (ex: pra uma pasta do OneDrive/Google
# Drive), mude aqui tambem, senao essa checagem "ja fiz hoje?" olha pra pasta errada
# (nao quebra nada, so faz o backup rodar de novo a toa em cada boot do dia).
$BackupDir = "C:\BKP-MAPOS"
$todayStamp = Get-Date -Format "yyyy-MM-dd"
$jaTemBackupHoje = (Test-Path $BackupDir) -and (Get-ChildItem -Path $BackupDir -Filter "mapos_backup_$todayStamp*.zip" -ErrorAction SilentlyContinue)

if ($jaTemBackupHoje) {
    Log "Ja existe backup de hoje ($todayStamp), pulando."
} else {
    Log "Nenhum backup de hoje ainda. Rodando backup.ps1..."
    try {
        & (Join-Path $ScriptDir "..\backup\backup.ps1") *>> $LogFile
        Log "Backup concluido."
    } catch {
        Log "AVISO: o backup falhou: $_"
    }
}

# --- 4. Abre o navegador no sistema ---
Log "Aguardando o servidor web responder..."
$webReady = $false
for ($i = 0; $i -lt 24; $i++) {
    try {
        $resp = Invoke-WebRequest -Uri $AppUrl -UseBasicParsing -TimeoutSec 3
        $webReady = $true
        break
    } catch {
        Start-Sleep -Seconds 5
    }
}

if ($webReady) {
    Log "Servidor respondendo. Abrindo navegador em $AppUrl"
} else {
    Log "AVISO: servidor nao respondeu a tempo, abrindo o navegador mesmo assim."
}
Start-Process $AppUrl

Log "===== start-mapos.ps1 concluido ====="
