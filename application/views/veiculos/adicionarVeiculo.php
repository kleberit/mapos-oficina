<script src="<?php echo base_url() ?>assets/js/jquery.mask.min.js"></script>
<style>
    .control-group.error .help-inline {
        display: flex;
    }

    .form-horizontal .control-group {
        border-bottom: 1px solid #ffffff;
    }

    .form-horizontal .controls {
        margin-left: 20px;
        padding-bottom: 8px 0;
    }

    .form-horizontal .control-label {
        text-align: left;
        padding-top: 15px;
    }

    .nopadding {
        padding: 0 20px !important;
        margin-right: 20px;
    }

    .widget-title h5 {
        padding-bottom: 30px;
        text-align-last: left;
        font-size: 2em;
        font-weight: 500;
    }

    #placa {
        text-transform: uppercase;
    }

    @media (max-width: 480px) {
        form {
            display: contents !important;
        }

        .form-horizontal .control-label {
            margin-bottom: -6px;
        }
    }
</style>
<div class="row-fluid" style="margin-top:0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title" style="margin: -20px 0 0">
                <span class="icon">
                    <i class='bx bx-car'></i>
                </span>
                <h5>Cadastro de Veículo</h5>
            </div>
            <?php if ($custom_error != '') {
                echo '<div class="alert alert-danger">' . $custom_error . '</div>';
            } ?>
            <form action="<?php echo current_url(); ?>" id="formVeiculo" method="post" class="form-horizontal">
                <div class="widget-content nopadding tab-content">
                    <div class="span6">
                        <div class="control-group">
                            <label for="placa" class="control-label">Placa<span class="required">*</span></label>
                            <div class="controls">
                                <input id="placa" type="text" name="placa" maxlength="10" value="<?php echo set_value('placa'); ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="modelo" class="control-label">Modelo<span class="required">*</span></label>
                            <div class="controls">
                                <input id="modelo" type="text" name="modelo" value="<?php echo set_value('modelo'); ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="ano" class="control-label">Ano</label>
                            <div class="controls">
                                <input id="ano" type="text" name="ano" placeholder="Ex: 2020/2021" value="<?php echo set_value('ano'); ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="span6">
                        <div class="control-group">
                            <label for="cor" class="control-label">Cor</label>
                            <div class="controls">
                                <input id="cor" type="text" name="cor" value="<?php echo set_value('cor'); ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="km" class="control-label">KM</label>
                            <div class="controls">
                                <input id="km" type="text" name="km" value="<?php echo set_value('km'); ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="chassi" class="control-label">Chassi</label>
                            <div class="controls">
                                <input id="chassi" type="text" name="chassi" value="<?php echo set_value('chassi'); ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <div class="span12">
                        <div class="span6 offset3" style="display:flex;justify-content: center">
                            <button type="submit" class="button btn btn-mini btn-success"><span class="button__icon"><i class='bx bx-save'></i></span> <span class="button__text2">Salvar</span></button>
                            <a title="Voltar" class="button btn btn-warning" href="<?php echo site_url() ?>/veiculos"><span class="button__icon"><i class="bx bx-undo"></i></span> <span class="button__text2">Voltar</span></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $("#km").mask('000000000', {
            reverse: false
        });

        $("#placa").focus();
        $('#formVeiculo').validate({
            rules: {
                placa: {
                    required: true
                },
                modelo: {
                    required: true
                },
            },
            messages: {
                placa: {
                    required: 'Campo Requerido.'
                },
                modelo: {
                    required: 'Campo Requerido.'
                },
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
    });
</script>
