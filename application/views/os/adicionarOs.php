<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>

<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />

<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <h5>Cadastro de OS</h5>
            </div>
            <div class="widget-content nopadding tab-content">
                <div class="span12" id="divProdutosServicos" style=" margin-left: 0">

                    <ul class="nav nav-tabs">
                        <li class="active" id="tabDetalhes"><a href="#tab1" data-toggle="tab">Detalhes da OS</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1">
                            <div class="span12" id="divCadastrarOs">
                                <?php if ($custom_error == true) { ?>
                                    <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente, responsável e garantia.<br />Ou se tem um cliente e um termo de garantia cadastrado.</div>
                                <?php
                                } ?>
                                <form action="<?php echo current_url(); ?>" method="post" id="formOs">
                                    <div class="span12" style="padding: 1%">
                                        <div class="span6">
                                            <label for="cliente">Cliente<span class="required">*</span></label>
                                            <input id="cliente" class="span12" type="text" name="cliente" value="" />
                                            <input id="clientes_id" class="span12" type="hidden" name="clientes_id" value="" />
                                        </div>
                                        <div class="span6">
                                            <label for="tecnico">Técnico / Responsável<span class="required">*</span></label>
                                            <input id="tecnico" class="span12" type="text" name="tecnico" value="<?= $this->session->userdata('nome_admin'); ?>" />
                                            <input id="usuarios_id" class="span12" type="hidden" name="usuarios_id" value="<?= $this->session->userdata('id_admin'); ?>" />
                                        </div>
                                    </div>
                                    <div class="span12" style="padding: 1%; overflow: auto;">
                                        <div class="span6">
                                            <label for="veiculo">Veículo (Placa)</label>
                                            <input id="veiculo" class="span12" type="text" name="veiculo" value="" autocomplete="off" />
                                            <input id="veiculos_id" class="span12" type="hidden" name="veiculos_id" value="" />
                                            <a href="#modal-veiculo-rapido" role="button" data-toggle="modal" class="button btn btn-mini btn-success" style="margin-top:5px; width: 200px;">
                                                <span class="button__icon"><i class='bx bx-plus-circle'></i></span><span class="button__text2">Cadastrar veículo</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span3">
                                            <label for="status">Status<span class="required">*</span></label>
                                            <select class="span12" name="status" id="status" value="">
                                                <option value="Aberto">Aberto</option>
                                                <option value="Orçamento">Orçamento</option>
                                                <option value="Negociação">Negociação</option>
                                                <option value="Aprovado">Aprovado</option>
                                                <option value="Aguardando Peças">Aguardando Peças</option>
                                                <option value="Em Andamento">Em Andamento</option>
                                                <option value="Finalizado">Finalizado</option>
                                                <option value="Faturado">Faturado</option>
                                                <option value="Cancelado">Cancelado</option>
                                            </select>
                                        </div>
                                        <div class="span3">
                                            <label for="dataInicial">Data Inicial<span class="required">*</span></label>
                                            <input id="dataInicial" autocomplete="off" class="span12 datepicker" type="text" name="dataInicial" value="<?php echo date('d/m/Y'); ?>" />
                                        </div>
                                        <div class="span3">
                                            <label for="dataFinal">Data Final<span class="required">*</span></label>
                                            <input id="dataFinal" autocomplete="off" class="span12 datepicker" type="text" name="dataFinal" value="" />
                                        </div>
                                        <div class="span3">
                                            <label for="garantia">Garantia (dias)</label>
                                            <input id="garantia" type="number" placeholder="Status s/g inserir nº/0" min="0" max="9999" class="span12" name="garantia" value="" />
                                            <?php echo form_error('garantia'); ?>
                                            <label for="termoGarantia">Termo Garantia</label>
                                            <input id="termoGarantia" class="span12" type="text" name="termoGarantia" value="" />
                                            <input id="garantias_id" class="span12" type="hidden" name="garantias_id" value="" />
                                        </div>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="descricaoProduto">
                                            <h4>Descrição Produto/Serviço</h4>
                                        </label>
                                        <textarea class="span12 editor" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5"></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="defeito">
                                            <h4>Defeito</h4>
                                        </label>
                                        <textarea class="span12 editor" name="defeito" id="defeito" cols="30" rows="5"></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes">
                                            <h4>Observações</h4>
                                        </label>
                                        <textarea class="span12 editor" name="observacoes" id="observacoes" cols="30" rows="5"></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="laudoTecnico">
                                            <h4>Laudo Técnico</h4>
                                        </label>
                                        <textarea class="span12 editor" name="laudoTecnico" id="laudoTecnico" cols="30" rows="5"></textarea>
                                    </div>
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span12" style="display:flex; justify-content: center;">
                                            <button class="button btn btn-success" id="btnContinuar">
                                              <span class="button__icon"><i class='bx bx-chevrons-right'></i></span><span class="button__text2">Continuar</span></button>
                                            <a href="<?php echo base_url() ?>index.php/os" class="button btn btn-mini btn-warning" style="max-width: 160px">
                                              <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                .
            </div>
        </div>
    </div>
</div>

<!-- Modal cadastro rápido de veículo -->
<div id="modal-veiculo-rapido" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabelVeiculo" aria-hidden="true">
    <form action="#" method="POST" id="formVeiculoRapido">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabelVeiculo">Cadastrar Veículo</h3>
        </div>
        <div class="modal-body">
            <div class="span12" id="divErroVeiculoRapido" style="margin-left: 0"></div>
            <div class="span6" style="margin-left: 0">
                <label for="placaRapida">Placa<span class="required">*</span></label>
                <input class="span12" type="text" name="placa" id="placaRapida" style="text-transform:uppercase" maxlength="10" />
            </div>
            <div class="span6" style="margin-left: 0">
                <label for="modeloRapido">Modelo<span class="required">*</span></label>
                <input class="span12" type="text" name="modelo" id="modeloRapido" />
            </div>
            <div class="span4" style="margin-left: 0">
                <label for="anoRapido">Ano</label>
                <input class="span12" type="text" name="ano" id="anoRapido" placeholder="Ex: 2020/2021" />
            </div>
            <div class="span4" style="margin-left: 0">
                <label for="corRapida">Cor</label>
                <input class="span12" type="text" name="cor" id="corRapida" />
            </div>
            <div class="span4" style="margin-left: 0">
                <label for="kmRapido">KM</label>
                <input class="span12" type="text" name="km" id="kmRapido" />
            </div>
            <div class="span12" style="margin-left: 0">
                <label for="chassiRapido">Chassi</label>
                <input class="span12" type="text" name="chassi" id="chassiRapido" />
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content: center">
            <button type="button" class="btn" data-dismiss="modal" aria-hidden="true" id="btn-close-veiculo-rapido">Fechar</button>
            <button class="btn btn-primary" type="submit">Cadastrar</button>
        </div>
    </form>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 1,
            select: function(event, ui) {
                $("#clientes_id").val(ui.item.id);
            }
        });
        $("#tecnico").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
            minLength: 1,
            select: function(event, ui) {
                $("#usuarios_id").val(ui.item.id);
            }
        });
        $("#termoGarantia").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteTermoGarantia",
            minLength: 1,
            select: function(event, ui) {
                $("#garantias_id").val(ui.item.id);
            }
        });
        $("#veiculo").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteVeiculo",
            minLength: 1,
            select: function(event, ui) {
                $("#veiculos_id").val(ui.item.id);
            }
        });
        $("#veiculo").on('input', function() {
            $("#veiculos_id").val('');
        });

        $("#formVeiculoRapido").validate({
            rules: {
                placa: {
                    required: true
                },
                modelo: {
                    required: true
                }
            },
            messages: {
                placa: {
                    required: 'Campo Requerido.'
                },
                modelo: {
                    required: 'Campo Requerido.'
                }
            },
            submitHandler: function(form) {
                var dados = $(form).serialize();
                $("#divErroVeiculoRapido").html('');

                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>index.php/os/adicionarVeiculoRapido",
                    data: dados,
                    dataType: 'json',
                    success: function(data) {
                        if (data.result == true) {
                            $("#veiculo").val(data.placa + ' - ' + data.modelo);
                            $("#veiculos_id").val(data.id);
                            $('#formVeiculoRapido')[0].reset();
                            $('#modal-veiculo-rapido').modal('hide');
                        } else {
                            $("#divErroVeiculoRapido").html('<div class="alert alert-danger">' + data.error + '</div>');
                        }
                    }
                });
                return false;
            }
        });

        $("#formOs").validate({
            rules: {
                cliente: {
                    required: true
                },
                tecnico: {
                    required: true
                },
                dataInicial: {
                    required: true
                },
                dataFinal: {
                    required: true
                }

            },
            messages: {
                cliente: {
                    required: 'Campo Requerido.'
                },
                tecnico: {
                    required: 'Campo Requerido.'
                },
                dataInicial: {
                    required: 'Campo Requerido.'
                },
                dataFinal: {
                    required: 'Campo Requerido.'
                }
            },
            errorClass: "help-inline",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').addClass('error');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').removeClass('error');
                $(element).parents('.control-group').addClass('success');
            }
        });
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });
    });
</script>
