# Backup do MAPOS (instalação Docker)

Dois scripts PowerShell para rodar no PC Windows que hospeda o MAPOS via Docker.

## `backup.ps1`

Gera um `.zip` com:
- Dump completo do banco de dados MySQL
- Pasta `assets/uploads` (anexos das O.S.)
- Pasta `assets/userImage` (fotos de usuário)
- `application/.env` (configurações e chaves)

Salva em `C:\BKP-MAPOS` (edite a variável `$BackupDir` no início do script se quiser outro
lugar — **o ideal é apontar para uma pasta sincronizada com a nuvem** — OneDrive, Google Drive
Desktop, Dropbox — assim o backup sai do PC automaticamente, sem custo e sem esforço extra).

Backups com mais de 30 dias são apagados automaticamente (ajustável em `$KeepDays`).

### Primeiro uso

1. Confira se `$DbPass` no topo do script bate com a senha do MySQL definida em `docker/.env`
   (`MYSQL_MAPOS_ROOT_PASSWORD`). **Troque a senha padrão (`root`) antes de ir pra produção**,
   e atualize aqui também.
2. Rode manualmente uma vez pra testar:
   ```powershell
   powershell -ExecutionPolicy Bypass -File .\backup.ps1
   ```
3. Confira se o `.zip` foi criado em `C:\BKP-MAPOS` e se tem tamanho razoável (não é só
   alguns KB vazios).

### Agendar (rodar sozinho todo dia)

1. Abra o **Agendador de Tarefas** do Windows (Task Scheduler).
2. "Criar Tarefa Básica" → nome "Backup MAPOS" → Diariamente → escolha um horário fora do
   expediente (ex: 23:30).
3. Ação: "Iniciar um programa"
   - Programa/script: `powershell.exe`
   - Argumentos: `-ExecutionPolicy Bypass -File "C:\caminho\completo\docker\backup\backup.ps1"`
4. Na aba **Geral** da tarefa, marque **"Executar mesmo que o usuário não esteja conectado"**
   (vai pedir a senha do Windows uma vez, pra guardar as credenciais) — assim o backup roda de
   madrugada mesmo sem ninguém logado no PC.
5. Na aba **Configurações**, marque **"Executar a tarefa assim que possível após uma
   inicialização agendada perdida"** — cobre o caso do PC estar desligado no horário do backup.

## `restore.ps1`

Restaura um `.zip` gerado pelo `backup.ps1`. **Substitui** o banco e os uploads atuais — use só
se tiver certeza (recuperação de problema, ou migração pra outro PC).

```powershell
powershell -ExecutionPolicy Bypass -File .\restore.ps1 -ZipFile "C:\BKP-MAPOS\mapos_backup_2026-08-21_23-30.zip"
```

## Importante

- **Teste a restauração pelo menos uma vez** antes de confiar no backup — restaure num PC de
  teste (ou numa cópia do projeto) e confira se o sistema abre normalmente com os dados.
- Backup só no mesmo PC não é backup de verdade — se o PC quebrar, pegar fogo ou for roubado,
  o backup local vai junto. Por isso a recomendação de salvar numa pasta sincronizada com a nuvem.
