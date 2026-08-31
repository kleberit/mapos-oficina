#requires -Version 5.1
<#
.SYNOPSIS
    Instalador completo do MAPOS - Oficina (do zero, um único comando).

.DESCRIPTION
    Feito para rodar numa máquina Windows "limpa" (sem Docker) e deixar tudo pronto:
      - Habilita WSL2 (reinicia o PC sozinho UMA vez, se precisar, e continua sozinho depois)
      - Instala o Docker Desktop
      - Baixa o código deste repositório
      - Gera senhas aleatórias pro banco de dados (não usa root/root)
      - Sobe os containers (nginx + php-fpm + mysql + phpmyadmin)
      - Roda a instalação do sistema direto no banco (sem precisar abrir o instalador web)
      - Cria a pasta de backup em C:\BKP-MAPOS
      - Configura o sistema pra subir sozinho toda vez que o PC ligar

.USO (rodar no PowerShell como Administrador)
    iwr -useb https://raw.githubusercontent.com/kleberit/mapos-oficina/main/install.ps1 -OutFile "$env:TEMP\mapos-install.ps1"
    powershell -ExecutionPolicy Bypass -File "$env:TEMP\mapos-install.ps1"

    O -ExecutionPolicy Bypass é necessário porque, por padrão, o Windows bloqueia a execução de
    scripts .ps1 baixados da internet (erro "UnauthorizedAccess" / "PSSecurityException").

    O script vai perguntar o nome, e-mail e senha do administrador do sistema logo no início.

.IMPORTANTE
    Isso ainda não foi testado numa máquina Windows real (foi escrito e revisado, mas não
    executado de ponta a ponta) — rode numa máquina de teste antes de confiar 100% nele pra
    uma instalação em produção. Cada etapa registra o que faz em install.log, na pasta de
    instalação, pra facilitar corrigir o que travar.
#>

param(
    [string]$InstallDir = "C:\MAPOS",
    [string]$BackupDir = "C:\BKP-MAPOS",
    [string]$RepoZipUrl = "https://github.com/kleberit/mapos-oficina/archive/refs/heads/main.zip",
    [switch]$Resume
)

$ErrorActionPreference = "Stop"
# Sem isso, o Invoke-WebRequest desenha a barra de progresso a cada pedaço baixado,
# o que deixa downloads grandes (instalador do Docker, zip do repo) MUITO mais lentos.
$ProgressPreference = "SilentlyContinue"
$StateDir = "C:\ProgramData\MAPOS-Install"
$StateFile = Join-Path $StateDir "state.json"
$LogFile = Join-Path $StateDir "install.log"
$TaskName = "MAPOS-Install-Resume"

# ============================================================
# Utilitários
# ============================================================

function Log($msg) {
    New-Item -ItemType Directory -Path $StateDir -Force | Out-Null
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - $msg"
    Add-Content -Path $LogFile -Value $line
    Write-Host $line
}

function Test-IsAdmin {
    $id = [Security.Principal.WindowsIdentity]::GetCurrent()
    $p = New-Object Security.Principal.WindowsPrincipal($id)
    return $p.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function New-RandomSecret([int]$Length = 24) {
    # gera o dobro de bytes brutos porque o replace abaixo descarta caracteres (+, /, =)
    $bytes = New-Object byte[] ($Length * 2)
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $clean = [Convert]::ToBase64String($bytes) -replace '[^a-zA-Z0-9]', ''
    return $clean.Substring(0, $Length)
}

function New-RandomBase64([int]$ByteLength = 32) {
    $bytes = New-Object byte[] $ByteLength
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    return [Convert]::ToBase64String($bytes)
}

function Get-LocalIPv4 {
    $ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notlike "127.*" -and
            $_.IPAddress -notlike "169.254.*" -and
            $_.InterfaceAlias -notmatch "Loopback|vEthernet|WSL"
        } | Select-Object -First 1 -ExpandProperty IPAddress
    return $ip
}

# ============================================================
# 0. Elevação e retomada pós-reboot
# ============================================================

if (-not (Test-IsAdmin)) {
    Write-Host "Este instalador precisa rodar como Administrador. Reabrindo elevado..."
    $elevateArgs = "-ExecutionPolicy Bypass -File `"$PSCommandPath`""
    if ($Resume) { $elevateArgs += " -Resume" }
    Start-Process powershell -Verb RunAs -ArgumentList $elevateArgs
    exit
}

New-Item -ItemType Directory -Path $StateDir -Force | Out-Null

if ($Resume) {
    Log "===== Retomando instalação após reinicialização ====="
    if (-not (Test-Path $StateFile)) {
        Log "ERRO: state.json não encontrado, não é possível retomar. Rode o instalador de novo do zero."
        exit 1
    }
    $state = Get-Content $StateFile -Raw | ConvertFrom-Json
    $InstallDir = $state.InstallDir
    $BackupDir = $state.BackupDir
    $RepoZipUrl = $state.RepoZipUrl
    $AdminName = $state.AdminName
    $AdminEmail = $state.AdminEmail
    $AdminPassword = $state.AdminPassword

    # Tarefa de retomada já cumpriu o papel dela
    schtasks /Delete /TN $TaskName /F 2>$null | Out-Null
} else {
    Log "===== Iniciando instalação do MAPOS ====="
    Write-Host ""
    Write-Host "=== Dados do administrador do sistema ===" -ForegroundColor Cyan
    $AdminName = Read-Host "Nome completo do administrador"
    $AdminEmail = Read-Host "E-mail do administrador (login)"
    $securePwd = Read-Host "Senha do administrador" -AsSecureString
    $AdminPassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePwd))

    if (-not $AdminName -or -not $AdminEmail -or -not $AdminPassword) {
        Log "ERRO: nome, e-mail e senha são obrigatórios."
        exit 1
    }

    $state = @{
        InstallDir    = $InstallDir
        BackupDir     = $BackupDir
        RepoZipUrl    = $RepoZipUrl
        AdminName     = $AdminName
        AdminEmail    = $AdminEmail
        AdminPassword = $AdminPassword
    }
    $state | ConvertTo-Json | Set-Content -Path $StateFile -Encoding utf8
}

# ============================================================
# 1. WSL2 / Virtual Machine Platform (pode exigir 1 reboot)
# ============================================================

function Test-WslReady {
    $wsl = Get-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux -ErrorAction SilentlyContinue
    $vmp = Get-WindowsOptionalFeature -Online -FeatureName VirtualMachinePlatform -ErrorAction SilentlyContinue
    return ($wsl.State -eq "Enabled") -and ($vmp.State -eq "Enabled")
}

if (-not (Test-WslReady)) {
    Log "WSL2/Virtual Machine Platform não habilitados. Habilitando..."
    Enable-WindowsOptionalFeature -Online -FeatureName Microsoft-Windows-Subsystem-Linux -All -NoRestart | Out-Null
    Enable-WindowsOptionalFeature -Online -FeatureName VirtualMachinePlatform -All -NoRestart | Out-Null

    Log "Registrando tarefa pra retomar a instalação sozinha após reiniciar..."
    $resumeCmd = "powershell.exe -ExecutionPolicy Bypass -File `"$StateDir\install.ps1`" -Resume"
    Copy-Item -Path $PSCommandPath -Destination (Join-Path $StateDir "install.ps1") -Force

    schtasks /Create /TN $TaskName /TR $resumeCmd /SC ONLOGON /RL HIGHEST /F | Out-Null

    Log "Reiniciando o computador em 15 segundos para concluir a habilitação do WSL2..."
    Log "(A instalação continua sozinha assim que o Windows terminar de logar de novo.)"
    Start-Sleep -Seconds 15
    Restart-Computer -Force
    exit
}

Log "WSL2/Virtual Machine Platform já habilitados."

# ============================================================
# 2. Docker Desktop
# ============================================================

$dockerCmd = Get-Command docker.exe -ErrorAction SilentlyContinue
if (-not $dockerCmd) {
    Log "Docker Desktop não encontrado. Baixando instalador..."
    $dockerInstaller = Join-Path $env:TEMP "DockerDesktopInstaller.exe"
    Invoke-WebRequest -Uri "https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe" -OutFile $dockerInstaller -UseBasicParsing

    Log "Instalando Docker Desktop silenciosamente (pode levar alguns minutos)..."
    Start-Process -FilePath $dockerInstaller -ArgumentList "install", "--quiet", "--accept-license", "--backend=wsl2" -Wait

    Log "Docker Desktop instalado."
} else {
    Log "Docker Desktop já instalado."
}

Log "Iniciando o Docker Desktop..."
$dockerDesktopExe = "C:\Program Files\Docker\Docker\Docker Desktop.exe"
if (Test-Path $dockerDesktopExe) {
    Start-Process $dockerDesktopExe
}

Log "Aguardando o Docker ficar pronto (isso pode levar de 1 a 3 minutos na primeira vez)..."
$dockerReady = $false
for ($i = 0; $i -lt 60; $i++) {
    docker info *> $null
    if ($LASTEXITCODE -eq 0) { $dockerReady = $true; break }
    Start-Sleep -Seconds 5
}
if (-not $dockerReady) {
    Log "ERRO: Docker não ficou pronto a tempo. Abra o Docker Desktop manualmente, espere ele terminar de iniciar, e rode o instalador de novo com -Resume."
    exit 1
}
Log "Docker pronto."

# ============================================================
# 3. Baixar o código do repositório
# ============================================================

Log "Baixando o MAPOS Oficina de $RepoZipUrl..."
$zipPath = Join-Path $env:TEMP "mapos-oficina.zip"
Invoke-WebRequest -Uri $RepoZipUrl -OutFile $zipPath -UseBasicParsing

$extractTmp = Join-Path $env:TEMP "mapos-oficina-extract"
if (Test-Path $extractTmp) { Remove-Item $extractTmp -Recurse -Force }
Expand-Archive -Path $zipPath -DestinationPath $extractTmp -Force

$extractedRoot = Get-ChildItem -Path $extractTmp -Directory | Select-Object -First 1

if (Test-Path $InstallDir) {
    Log "AVISO: $InstallDir já existe. Os arquivos serão sobrescritos (dados do MySQL, se houver, ficam no volume Docker, não aqui)."
} else {
    New-Item -ItemType Directory -Path $InstallDir -Force | Out-Null
}
Copy-Item -Path (Join-Path $extractedRoot.FullName "*") -Destination $InstallDir -Recurse -Force
Log "Código copiado para $InstallDir."

# ============================================================
# 4. Pasta de backup
# ============================================================

New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
Log "Pasta de backup pronta em $BackupDir."

# Se -BackupDir foi customizado (diferente do padrão C:\BKP-MAPOS já embutido nos scripts),
# propaga o valor pra dentro do backup.ps1 e do start-mapos.ps1
$backupScriptPath = Join-Path $InstallDir "docker\backup\backup.ps1"
$startScriptForBackupPatch = Join-Path $InstallDir "docker\startup\start-mapos.ps1"
foreach ($p in @($backupScriptPath, $startScriptForBackupPatch)) {
    if (Test-Path $p) {
        $content = Get-Content $p -Raw
        $content = $content -replace '\$BackupDir = "C:\\BKP-MAPOS"', "`$BackupDir = `"$BackupDir`""
        Set-Content -Path $p -Value $content -Encoding utf8
    }
}

# ============================================================
# 5. Gerar segredos e escrever os .env
# ============================================================

Log "Gerando senhas e chaves de segurança..."
$dbRootPassword = New-RandomSecret 24
$dbAppPassword = New-RandomSecret 24
$encryptionKey = New-RandomSecret 32
$jwtKey = New-RandomBase64 32

$localIp = Get-LocalIPv4
if (-not $localIp) {
    Log "Não consegui detectar o IP local automaticamente."
    $localIp = Read-Host "Digite o IP local desta máquina (ex: 192.168.1.50)"
}
Log "IP local detectado/usado: $localIp"
$baseUrl = "http://$localIp:8000/"

# --- docker/.env ---
$dockerEnvPath = Join-Path $InstallDir "docker\.env"
$dockerEnv = Get-Content $dockerEnvPath -Raw
$dockerEnv = $dockerEnv -replace 'MYSQL_MAPOS_ROOT_PASSWORD=root', "MYSQL_MAPOS_ROOT_PASSWORD=$dbRootPassword"
$dockerEnv = $dockerEnv -replace 'MYSQL_MAPOS_PASSWORD=mapos', "MYSQL_MAPOS_PASSWORD=$dbAppPassword"
Set-Content -Path $dockerEnvPath -Value $dockerEnv -Encoding utf8
Log "docker\.env atualizado com senhas geradas."

# --- application/.env (a partir do .env.example, igual o instalador web faz) ---
$envExamplePath = Join-Path $InstallDir "application\.env.example"
$envPath = Join-Path $InstallDir "application\.env"
$envContent = Get-Content $envExamplePath -Raw
$envContent = $envContent -replace 'enter_baseurl', $baseUrl
$envContent = $envContent -replace 'enter_encryption_key', $encryptionKey
$envContent = $envContent -replace 'enter_db_hostname', 'mysql'
$envContent = $envContent -replace 'enter_db_username', 'mapos'
$envContent = $envContent -replace 'enter_db_password', $dbAppPassword
$envContent = $envContent -replace 'enter_db_name', 'mapos'
$envContent = $envContent -replace 'enter_jwt_key', $jwtKey
$envContent = $envContent -replace 'enter_token_expire_time', '86400'
$envContent = $envContent -replace 'enter_api_enabled', 'true'
$envContent = $envContent -replace 'pre_installation', 'production'
# Endurecendo pra produção: não expor tela de erro detalhada pro usuário final
$envContent = $envContent -replace 'WHOOPS_ERROR_PAGE_ENABLED=true', 'WHOOPS_ERROR_PAGE_ENABLED=false'
Set-Content -Path $envPath -Value $envContent -Encoding utf8
Log "application\.env criado."

# ============================================================
# 6. Subir os containers
# ============================================================

Log "Subindo os containers (isso demora mais na primeira vez, precisa buildar as imagens)..."
Push-Location (Join-Path $InstallDir "docker")
docker compose up -d --build 2>&1 | Tee-Object -FilePath $LogFile -Append
Pop-Location

Log "Aguardando o MySQL ficar pronto..."
$mysqlReady = $false
for ($i = 0; $i -lt 60; $i++) {
    docker exec mysql mysqladmin ping -u root "-p$dbRootPassword" --silent *> $null
    if ($LASTEXITCODE -eq 0) { $mysqlReady = $true; break }
    Start-Sleep -Seconds 5
}
if (-not $mysqlReady) {
    Log "ERRO: MySQL não ficou pronto a tempo. Confira 'docker logs mysql' manualmente."
    exit 1
}
Log "MySQL pronto."

# ============================================================
# 7. Instalar o sistema direto no banco (sem passar pelo instalador web)
# ============================================================

Log "Criando o usuário administrador e montando o schema do banco..."

$adminPasswordHash = ($AdminPassword | docker exec -i php-fpm php -r 'echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT);').Trim()
if (-not $adminPasswordHash) {
    Log "ERRO: não consegui gerar o hash da senha do administrador."
    exit 1
}

$bancoSqlPath = Join-Path $InstallDir "banco.sql"
$sql = Get-Content $bancoSqlPath -Raw
$now = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$sql = $sql -replace 'admin_name', $AdminName
$sql = $sql -replace 'admin_email', $AdminEmail
$sql = $sql -replace 'admin_password', $adminPasswordHash
$sql = $sql -replace 'admin_created_at', $now

$tmpSql = Join-Path $env:TEMP "mapos_install.sql"
Set-Content -Path $tmpSql -Value $sql -Encoding utf8

Get-Content $tmpSql | docker exec -i mysql mysql -uroot "-p$dbRootPassword" mapos
if ($LASTEXITCODE -ne 0) {
    Log "ERRO ao importar o banco de dados."
    exit 1
}
Remove-Item $tmpSql -Force
Log "Banco de dados criado e usuário administrador cadastrado."

# Apaga o estado que continha a senha em texto puro assim que ela deixa de ser necessária
if (Test-Path $StateFile) { Remove-Item $StateFile -Force }

# O instalador web (install/) não é usado por este script (instalamos direto no banco) e o
# próprio sistema recomenda removê-lo por segurança após a instalação.
$installFolder = Join-Path $InstallDir "install"
if (Test-Path $installFolder) {
    Remove-Item $installFolder -Recurse -Force
    Log "Pasta install/ removida por segurança."
}

# ============================================================
# 8. Verificação
# ============================================================

Log "Verificando se o sistema está respondendo..."
Start-Sleep -Seconds 5
try {
    $resp = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing -TimeoutSec 15
    Log "Sistema respondendo (HTTP $($resp.StatusCode))."
} catch {
    Log "AVISO: não consegui confirmar a resposta HTTP ainda. Pode levar mais alguns segundos - confira manualmente em $baseUrl"
}

# ============================================================
# 9. Subida automática ao ligar o PC
# ============================================================

Log "Configurando a subida automática no logon do Windows..."
$startScript = Join-Path $InstallDir "docker\startup\start-mapos.ps1"
$startAction = "powershell.exe -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$startScript`""
schtasks /Create /TN "MAPOS-Autostart" /TR $startAction /SC ONLOGON /DELAY 0000:30 /RL HIGHEST /F | Out-Null
Log "Tarefa 'MAPOS-Autostart' criada. O sistema vai subir sozinho a cada logon do Windows."
Log "Pra funcionar sem ninguém precisar digitar senha ao ligar o PC, configure o login automático do Windows: veja docker\startup\README.md."

# ============================================================
# Resumo final
# ============================================================

Write-Host ""
Write-Host "=====================================================" -ForegroundColor Green
Write-Host " Instalação concluída!" -ForegroundColor Green
Write-Host "====================================================="
Write-Host " Sistema:  $baseUrl"
Write-Host " Login:    $AdminEmail"
Write-Host " Backups:  $BackupDir (script pronto em docker\backup\backup.ps1)"
Write-Host " Log:      $LogFile"
Write-Host ""
Write-Host " Próximos passos recomendados:"
Write-Host " 1. Abra $baseUrl e confirme o login."
Write-Host " 2. Configure o login automático do Windows (docker\startup\README.md)"
Write-Host "    para o sistema subir sozinho quando o PC for ligado sem ninguém logar manualmente."
Write-Host " 3. Rode docker\backup\backup.ps1 uma vez manualmente pra confirmar que o backup funciona."
Write-Host "====================================================="
