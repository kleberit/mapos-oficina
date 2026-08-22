<div class="widget-box">
    <div class="widget-title" style="margin: -20px 0 0">
        <span class="icon">
            <i class='bx bx-car'></i>
        </span>
        <h5>Veículo: <?php echo $result->placa; ?> - <?php echo $result->modelo; ?></h5>
    </div>
    <div class="widget-content tab-content">
        <div class="accordion" id="collapse-group">
            <div class="accordion-group widget-box">
                <div class="accordion-heading">
                    <div class="widget-title">
                        <a data-parent="#collapse-group" href="#collapseGOne" data-toggle="collapse">
                            <span><i class='bx bx-car icon-cli'></i></span>
                            <h5 style="padding-left: 28px">Dados do Veículo</h5>
                        </a>
                    </div>
                </div>
                <div class="collapse in accordion-body" id="collapseGOne">
                    <div class="widget-content">
                        <table class="table table-bordered" style="border: 1px solid #ddd">
                            <tbody>
                                <tr>
                                    <td style="text-align: right; width: 30%"><strong>Placa</strong></td>
                                    <td><?php echo $result->placa; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right"><strong>Modelo</strong></td>
                                    <td><?php echo $result->modelo; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right"><strong>Ano</strong></td>
                                    <td><?php echo $result->ano; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right"><strong>Cor</strong></td>
                                    <td><?php echo $result->cor; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right"><strong>KM</strong></td>
                                    <td><?php echo $result->km; ?></td>
                                </tr>
                                <tr>
                                    <td style="text-align: right"><strong>Chassi</strong></td>
                                    <td><?php echo $result->chassi; ?></td>
                                </tr>
                                <?php if ($result->dataCadastro) { ?>
                                    <tr>
                                        <td style="text-align: right"><strong>Data de Cadastro</strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($result->dataCadastro)); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eVeiculo')) { ?>
                            <a href="<?= base_url() ?>index.php/veiculos/editar/<?= $result->idVeiculos ?>" class="button btn btn-mini btn-success">
                                <span class="button__icon"><i class='bx bx-edit'></i></span><span class="button__text2">Editar</span>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="accordion-group widget-box">
                <div class="accordion-heading">
                    <div class="widget-title">
                        <a data-parent="#collapse-group" href="#collapseGTwo" data-toggle="collapse">
                            <span><i class='bx bx-wrench icon-cli'></i></span>
                            <h5 style="padding-left: 28px">Histórico de Ordens de Serviço</h5>
                        </a>
                    </div>
                </div>
                <div class="collapse in accordion-body" id="collapseGTwo">
                    <div class="widget-content">
                        <table class="table table-bordered" style="border: 1px solid #ddd">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Cliente</th>
                                    <th>Data Inicial</th>
                                    <th>Status</th>
                                    <th>Valor Total</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (! $results) {
                                    echo '<tr><td colspan="6">Nenhuma Ordem de Serviço encontrada para este veículo.</td></tr>';
                                }
                                foreach ($results as $r) {
                                    echo '<tr>';
                                    echo '<td>' . $r->idOs . '</td>';
                                    echo '<td>' . $r->nomeCliente . '</td>';
                                    echo '<td>' . ($r->dataInicial ? date('d/m/Y', strtotime($r->dataInicial)) : '') . '</td>';
                                    echo '<td>' . $r->status . '</td>';
                                    echo '<td>' . number_format($r->valorTotal, 2, ',', '.') . '</td>';
                                    echo '<td><a href="' . base_url() . 'index.php/os/visualizar/' . $r->idOs . '" class="btn-nwe" title="Ver O.S."><i class="bx bx-show bx-xs"></i></a></td>';
                                    echo '</tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
