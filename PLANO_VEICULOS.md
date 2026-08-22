# Plano: Módulo de Veículos + integração com O.S. (MAPOS → oficina mecânica)

> Status: **implementado** (branch `feature/veiculos-oficina`, ainda não commitado/mergeado). Este arquivo fica como documentação de referência do que foi feito.
> Cópia do plano original também salva em `/home/kleber/.claude/plans/coloquei-a-pasta-mapos-linked-pixel.md`.

## Contexto

O MAPOS instalado (raiz do repo `/mnt/c/Users/Kleber/Documents/Proejtos/Map-OS_v4.54.0`, servido via Docker em `localhost:8000`) é um sistema genérico de controle de Ordens de Serviço, sem noção de veículo. Objetivo: adaptar pra oficina mecânica — cadastrar veículos (Placa\*, Modelo\*, Ano, Cor, KM, Chassi — só Placa e Modelo obrigatórios) e associá-los à O.S. além do cliente, buscando por placa, propagando o dado pra impressão e relatórios.

**Decisão validada com o usuário:** veículo é uma entidade independente, sem vínculo fixo a um cliente — familiares diferentes podem trazer o mesmo carro em O.S. diferentes. `os` terá `clientes_id` e `veiculos_id` como colunas irmãs e independentes; nenhum veículo fica "preso" a um cliente. Também é requisito ter uma tela de **histórico por veículo** (todas as O.S. já feitas nele, navegável, mesmo com clientes diferentes). Quando a placa buscada na O.S. não existe, abre **modal de cadastro rápido** (mesmo padrão AJAX do modal "Adicionar Anotação" já existente em `editarOs.php`), sem sair da tela.

Existe uma subpasta solta `mapos/` (clone git separado, não rastreado, não montado no Docker) — **ignorá-la**; todo trabalho é na raiz do repo, que é o código realmente servido e instalado.

Todos os padrões abaixo foram confirmados lendo o código atual (não são suposições): módulo Clientes/Garantias como referência de CRUD, autocomplete de cliente em `Os.php`/`Os_model.php`, modal AJAX de anotação em `editarOs.php`, migrations CodeIgniter ativas (`migration_enabled = true` em `application/config/migration.php`), sistema de permissões hardcoded em `Permissoes.php` + views + seed.

## Decisões de design (já fechadas)

- `os.veiculos_id`: **NULLABLE**, sem `required` — nem toda O.S. precisa ter veículo (mantém o sistema genérico). O obrigatório é só Placa+Modelo dentro do cadastro do veículo.
- `veiculos.placa`: índice **único** (identificador natural do veículo, evita duplicidade).
- `veiculos.clientes_id`: campo opcional, só "proprietário/contato de referência" pra pré-preencher o cadastro — **nunca** usado para restringir qual veículo pode ser escolhido em qual O.S.
- FKs `os.veiculos_id → veiculos.idVeiculos` e `veiculos.clientes_id → clientes.idClientes` com `ON DELETE SET NULL` (exclusão de cliente/veículo nunca quebra O.S. de terceiros).
- `Veiculos::excluir()` bloqueia (flash error) se houver qualquer O.S. vinculada ao veículo — checagem na camada de aplicação, além da rede de segurança do `ON DELETE SET NULL` no banco.

## Fase 1 — Banco de dados

Criar duas migrations em `application/database/migrations/` (gerar via `php index.php tools migration "nome"` dentro do container `php-fpm`, depois editar o conteúdo):

1. `..._create_veiculos_table.php` — via `dbforge`: `idVeiculos` PK auto_increment, `placa` VARCHAR(10) NOT NULL + índice único, `modelo` VARCHAR(100) NOT NULL, `ano` VARCHAR(9) NULL, `cor` VARCHAR(30) NULL, `km` INT NULL, `chassi` VARCHAR(30) NULL, `clientes_id` INT NULL (FK `ON DELETE SET NULL`), `dataCadastro` DATE NULL. `ENGINE=InnoDB`, `utf8mb4_general_ci`.
2. `..._add_veiculos_id_to_os_table.php` — `ALTER TABLE os ADD veiculos_id INT(11) NULL AFTER clientes_id` + índice + FK `fk_os_veiculos1 → veiculos.idVeiculos ON DELETE SET NULL`.

Comandos para gerar (dentro do container `php-fpm`):
```
docker exec php-fpm php index.php tools migration "create_veiculos_table"
docker exec php-fpm php index.php tools migration "add_veiculos_id_to_os_table"
```
Depois editar o conteúdo dos dois arquivos gerados em `application/database/migrations/` com o SQL/dbforge descrito acima.

Atualizar `banco.sql` (raiz) pra refletir o schema final, já que o instalador (`install/do_install.php`) importa esse arquivo do zero e não roda migrations:
- Inserir `CREATE TABLE IF NOT EXISTS veiculos` logo após `clientes` e **antes** de `os` (ordem importa pra FK resolver).
- Adicionar `veiculos_id` + índice + constraint no `CREATE TABLE os`.

## Fase 2 — CRUD standalone de Veículos

- `application/models/Veiculos_model.php` (novo) — mesmo boilerplate manual de `Clientes_model.php`/`Garantias_model.php` (sem ORM): `get/getById/add/edit/delete/count`, mais `getByPlaca($placa)`, `placaExists($placa, $id=null)`, `getOsByVeiculo($id)` (join com `clientes` pra mostrar quem trouxe o carro em cada O.S.), `autoCompleteVeiculo($q)` (mesmo padrão de `Os_model::autoCompleteCliente()`: `like` em `placa`/`modelo`, `echo json_encode([...])`, label tipo `"PLACA - Modelo (Ano/Cor)"`).
- `application/controllers/Veiculos.php` (novo) — espelha `Garantias.php`: `index→gerenciar()`, `gerenciar()` (lista + busca), `adicionar()`, `editar()`, `visualizar()` (mostra dados do veículo + `getOsByVeiculo` = histórico), `excluir()` (bloqueia se houver O.S. vinculada). Cada método checando permissão `vVeiculo`/`aVeiculo`/`eVeiculo`/`dVeiculo`.
- Views novas em `application/views/veiculos/`: `veiculos.php` (lista, espelha `clientes/clientes.php`), `adicionarVeiculo.php`, `editarVeiculo.php` (form Bootstrap 2 `.form-horizontal`, campos Placa\*/Modelo\*/Ano/Cor/KM/Chassi, validação client-side `jquery.validate.js`), `visualizar.php` (dados do veículo + tabela "Histórico de O.S." com link pra `os/visualizarOs/{id}` de cada linha — requisito de navegação por histórico).
- `application/config/form_validation.php` — nova chave `'veiculos'`: `placa` (`required|max_length[10]|unique[veiculos.placa.<id>.idVeiculos]`, mesmo padrão de `clientes.documento`), `modelo` (`required`), demais campos sem `required`.
- `application/views/tema/menu.php` — novo item "Veículos" (mesmo formato do item Clientes, condicionado a `vVeiculo`).

## Fase 3 — Permissões (`vVeiculo`/`aVeiculo`/`eVeiculo`/`dVeiculo`)

Sistema de permissões não é dinâmico — tocar em 4 lugares, sempre seguindo o padrão exato já usado para `*Cliente`:
1. `application/controllers/Permissoes.php` — adicionar as 4 chaves no array `$permissoes`, nos métodos `adicionar()` e `editar()`.
2. `application/views/permissoes/adicionarPermissao.php` e `editarPermissao.php` — duplicar o bloco accordion "Clientes" como "Veículos" com os 4 checkboxes.
3. `application/database/seeds/Permissoes.php` — string serializada do perfil Administrador: `a:53:{...}` → `a:57:{...}`, inserindo `aVeiculo/eVeiculo/dVeiculo/vVeiculo` (mesmo formato `s:8:"xVeiculo";s:1:"1";` dos demais, confirmado por contagem de caracteres).
4. `banco.sql` (INSERT do perfil Administrador) — mesma alteração da string serializada, pra instalações novas já virem com acesso.

## Fase 4 — Integração no formulário de O.S. (autocomplete por placa + cadastro rápido)

- `application/controllers/Os.php`: novo `autoCompleteVeiculo()` (delega a `veiculos_model->autoCompleteVeiculo($q)`, mesmo esqueleto de `autoCompleteCliente()`); novo `adicionarVeiculoRapido()` (AJAX, valida `form_validation->run('veiculos')`, checa `placaExists`, insere, `echo json_encode(['result'=>true,'id'=>...,'placa'=>...,'modelo'=>...])`) — mesmo esqueleto do `adicionarAnotacao()` já existente; `adicionar()`/`editar()` passam a incluir `'veiculos_id' => $this->input->post('veiculos_id') ?: null` no array `$data`.
- `application/models/Os_model.php`: `getById()`, `getOs()` e `get()` ganham `LEFT JOIN veiculos ON veiculos.idVeiculos = os.veiculos_id` + `veiculos.placa, veiculos.modelo, veiculos.ano, veiculos.cor, veiculos.km, veiculos.chassi` no select (sem colisão de nomes com os selects atuais).
- `application/views/os/adicionarOs.php` e `editarOs.php`: novo campo "Veículo (Placa)" ao lado do campo Cliente — input texto `#veiculo` + hidden `#veiculos_id`, autocomplete jQuery UI apontando pra `os/autoCompleteVeiculo` (mesmo padrão do `#cliente`), botão "Cadastrar veículo" que abre modal (`#modal-veiculo-rapido`, mesmo esqueleto Bootstrap 2 do `#modal-anotacao`) com os 6 campos; submit via AJAX pra `os/adicionarVeiculoRapido`, preenchendo `#veiculo`/`#veiculos_id` na resposta de sucesso (sem reload). Campo fica **opcional** na validação client-side (sem `required`), consistente com a decisão de `veiculos_id` nullable.

## Fase 5 — Impressão, visualização e relatórios

- `application/views/os/imprimirOs.php` — bloco "DADOS DO VEÍCULO" (Placa, Modelo, Ano/Cor, KM), condicional a `!empty($result->veiculos_id)`, duplicado nas 2 ocorrências existentes do bloco de cliente (2 vias: cliente + empresa).
- `application/views/os/imprimirOsTermica.php` — versão condensada do mesmo bloco (cupom 80mm).
- `application/views/os/visualizarOs.php` e `application/views/os/emails/os.php` — mesmo bloco condicional, formato mais simples.
- `application/models/Relatorios_model.php` (`osRapid()`, `osCustom()`) — `LEFT JOIN veiculos` + `veiculos.placa, veiculos.modelo` no select.
- `application/views/relatorios/rel_os.php`, `rel_os_topo.php`, `relatorios/imprimir/imprimirOs.php` — nova coluna Placa (opcionalmente Modelo).
- `application/views/os/os.php` (listagem) — coluna Placa opcional/nice-to-have, já disponível de graça após a Fase 4 (mesmo `getOs()`).

## Fase 6 (opcional, baixa prioridade)

API REST `application/controllers/api/v1/VeiculosController.php` espelhando `ClientesController.php` + rota em `routes_api.php`. Não bloqueia o objetivo do usuário — só se pedir depois.

## Verificação end-to-end (quando for implementar)

1. `docker ps` — confirmar `nginx`/`php-fpm`/`mysql` rodando.
2. Gerar migrations dentro do container: `docker exec php-fpm php index.php tools migration "create_veiculos_table"` e `"add_veiculos_id_to_os_table"`, editar o conteúdo gerado.
3. Rodar: `docker exec php-fpm php index.php tools migrate` (ou via navegador, `Mapos::atualizarBanco()`, permissão `cSistema`).
4. Conferir schema: `docker exec mysql mysql -uroot -proot mapos -e "DESCRIBE veiculos; DESCRIBE os;"`.
5. Permissões: no banco de teste já existente, mais seguro **marcar os 4 novos checkboxes via tela `Permissoes`** no navegador do que editar o blob serializado manualmente (erro de contagem `a:N:` corrompe o `unserialize`).
6. Teste funcional no navegador:
   - `veiculos/adicionar` com só Placa+Modelo → salva; placa duplicada → bloqueia.
   - `os/adicionar`: digitar placa existente → autocomplete preenche; placa inexistente → modal de cadastro rápido preenche `#veiculo`/`#veiculos_id` sem reload.
   - Salvar O.S., reabrir em `os/editar/{id}` → veículo pré-carregado.
   - `os/imprimirOs/{id}` e `os/imprimirOsTermica/{id}` → bloco "DADOS DO VEÍCULO" aparece (e some quando a O.S. não tem veículo).
   - `veiculos/visualizar/{id}` → histórico de O.S. do veículo, inclusive O.S. de clientes diferentes no mesmo carro (testar criando 2 O.S. com clientes diferentes e mesma placa).
   - Relatório de O.S. → coluna Placa aparece.
   - `veiculos/excluir` de um veículo com O.S. vinculada → bloqueado com flash error.
   - Regressão: criar O.S. **sem** veículo continua funcionando (impressão/e-mail não quebram com `veiculos_id` NULL).

### Arquivos críticos
- `application/database/migrations/` (2 novas)
- `application/models/Veiculos_model.php`, `application/controllers/Veiculos.php`, `application/views/veiculos/*`
- `application/controllers/Os.php`, `application/models/Os_model.php`
- `application/views/os/adicionarOs.php`, `application/views/os/editarOs.php`, `application/views/os/imprimirOs.php`
- `application/controllers/Permissoes.php`, `application/views/permissoes/*Permissao.php`, `application/database/seeds/Permissoes.php`
- `banco.sql`

---

## Como retomar

Quando voltar, é só pedir para o Claude Code: **"implementa o plano do PLANO_VEICULOS.md"** — ele segue direto da Fase 1 em diante, sem precisar reexplorar o código.
