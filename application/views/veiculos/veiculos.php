<div class="new122">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class='bx bx-car'></i>
        </span>
        <h5>Veículos</h5>
    </div>
    <div class="span12" style="margin-left: 0">
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aVeiculo')) { ?>
            <div class="span3">
                <a href="<?= base_url() ?>index.php/veiculos/adicionar" class="button btn btn-mini btn-success"
                    style="max-width: 165px">
                    <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">
                        Veículo
                    </span>
                </a>
            </div>
        <?php } ?>
        <form class="span9" method="get" action="<?= base_url() ?>index.php/veiculos"
            style="display: flex; justify-content: flex-end;">
            <div class="span3">
                <input type="text" name="pesquisa" id="pesquisa"
                    placeholder="Buscar por Placa, Modelo ou Chassi..." class="span12"
                    value="<?= $this->input->get('pesquisa') ?>">
            </div>
            <div class="span1">
                <button class="button btn btn-mini btn-warning" style="min-width: 30px">
                    <span class="button__icon"><i class='bx bx-search-alt'></i></span></button>
            </div>
        </form>
    </div>

    <div class="widget-box">
        <h5 style="padding: 3px 0"></h5>
        <div class="widget-content nopadding tab-content">
            <table id="tabela" class="table table-bordered ">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Modelo</th>
                        <th>Ano</th>
                        <th>Cor</th>
                        <th>KM</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (! $results) {
                        echo '<tr>
                    <td colspan="6">Nenhum Veículo Cadastrado</td>
                  </tr>';
                    }
                    foreach ($results as $r) {
                        echo '<tr>';
                        echo '<td><a href="' . base_url() . 'index.php/veiculos/visualizar/' . $r->idVeiculos . '" style="margin-right: 1%"><b>' . $r->placa . '</b></a></td>';
                        echo '<td>' . $r->modelo . '</td>';
                        echo '<td>' . $r->ano . '</td>';
                        echo '<td>' . $r->cor . '</td>';
                        echo '<td>' . $r->km . '</td>';

                        echo '<td>';
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vVeiculo')) {
                            echo '<a href="' . base_url() . 'index.php/veiculos/visualizar/' . $r->idVeiculos . '" style="margin-right: 1%" class="btn-nwe" title="Ver mais detalhes"><i class="bx bx-show bx-xs"></i></a>';
                        }
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eVeiculo')) {
                            echo '<a href="' . base_url() . 'index.php/veiculos/editar/' . $r->idVeiculos . '" style="margin-right: 1%" class="btn-nwe3" title="Editar Veículo"><i class="bx bx-edit bx-xs"></i></a>';
                        }
                        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dVeiculo')) {
                            echo '<a href="#modal-excluir" role="button" data-toggle="modal" veiculo="' . $r->idVeiculos . '" style="margin-right: 1%" class="btn-nwe4" title="Excluir Veículo"><i class="bx bx-trash-alt bx-xs"></i></a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>

        </div>
    </div>
</div>
<?php echo $this->pagination->create_links(); ?>

<!-- Modal -->
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <form action="<?php echo base_url() ?>index.php/veiculos/excluir" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel">Excluir Veículo</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idVeiculo" name="id" value="" />
            <h5 style="text-align: center">Deseja realmente excluir este veículo?</h5>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button class="button btn btn-warning" data-dismiss="modal" aria-hidden="true"><span class="button__icon"><i
                        class="bx bx-x"></i></span><span class="button__text2">Cancelar</span></button>
            <button class="button btn btn-danger"><span class="button__icon"><i class='bx bx-trash'></i></span> <span
                    class="button__text2">Excluir</span></button>
        </div>
    </form>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', 'a', function(event) {
            var veiculo = $(this).attr('veiculo');
            if (veiculo) {
                $('#idVeiculo').val(veiculo);
            }
        });
    });
</script>
