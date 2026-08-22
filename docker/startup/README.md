# Subida automática do MAPOS ao ligar o PC

Como o PC da oficina **não fica ligado 24/7**, a ideia é: liga o PC de manhã, espera um
pouquinho, e o sistema já sobe sozinho — containers no ar, backup do fechamento do dia
anterior feito, e o navegador já abre na tela de login.

Isso depende de 3 coisas, nessa ordem:

## 1. Docker Desktop iniciar sozinho com o Windows

Docker Desktop → ⚙️ Settings → General → marque **"Start Docker Desktop when you log in"**.

## 2. O Windows logar sozinho (sem precisar digitar senha)

Sem isso, o PC liga mas fica parado na tela de login esperando alguém digitar a senha — e o
Docker Desktop nunca chega a iniciar.

**Opção recomendada** (mais segura que editar registro na mão): baixe o **Autologon**, da
Microsoft/Sysinternals (gratuito, oficial):
👉 https://learn.microsoft.com/sysinternals/downloads/autologon

Abra o `Autologon.exe`, preencha usuário, domínio (deixe o nome do PC) e senha do Windows,
clique **Enable**. Pronto — a senha fica guardada de forma criptografada, não em texto puro.

## 3. Rodar o `start-mapos.ps1` assim que o Windows logar

1. Abra o **Agendador de Tarefas** (Task Scheduler).
2. **Criar Tarefa** (não a "Tarefa Básica" — precisamos de uma opção que só a versão completa
   tem).
3. Aba **Geral**:
   - Nome: `Iniciar MAPOS`
   - Marque **"Executar estando o usuário conectado ou não"** — não, nesse caso deixe
     **"Executar somente quando o usuário estiver conectado"** (é o padrão) — como o login é
     automático, essa tarefa vai disparar assim que o Windows terminar de logar sozinho.
4. Aba **Disparadores** → Novo:
   - "Ao fazer logon" → Qualquer usuário (ou o usuário específico do autologon)
   - Em "Configurações avançadas", adicione um **atraso de 30 segundos** (dá um tempo pro
     Windows terminar de carregar antes do script começar a checar o Docker).
5. Aba **Ações** → Nova:
   - Programa/script: `powershell.exe`
   - Argumentos: `-WindowStyle Hidden -ExecutionPolicy Bypass -File "C:\caminho\completo\docker\startup\start-mapos.ps1"`
     (troque pelo caminho real onde está o projeto)
6. Aba **Condições**: desmarque "Iniciar a tarefa somente se o computador estiver com
   alimentação AC" (irrelevante aqui, mas evita problema em notebook).
7. Salve.

## Testando

Reinicie o PC e cronometre: em 1-3 minutos (dependendo do PC) o navegador deve abrir sozinho
na tela de login do MAPOS. Se não abrir, confira o arquivo `start-mapos.log` nesta mesma pasta
— ele registra cada etapa e onde travou.

## Sobre desligar o PC

Prefira sempre **Iniciar → Desligar** em vez de segurar o botão físico de força. Desligamentos
forçados repetidos podem, com o tempo, corromper o disco virtual que o Docker Desktop usa
(dentro do WSL2) — o que causaria o Docker não subir mais na manhã seguinte. Desligar pelo
menu dá tempo do Docker Desktop encerrar os containers de forma limpa antes do Windows desligar.
