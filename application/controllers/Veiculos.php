<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Veiculos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('veiculos_model');
        $this->data['menuVeiculos'] = 'veiculos';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vVeiculo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar veículos.');
            redirect(base_url());
        }

        $pesquisa = $this->input->get('pesquisa');

        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('veiculos/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->veiculos_model->count('veiculos');
        if ($pesquisa) {
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}";
            $this->data['configuration']['first_url'] = base_url('index.php/veiculos') . "?pesquisa={$pesquisa}";
        }

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->veiculos_model->get('veiculos', '*', $pesquisa, $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'veiculos/veiculos';

        return $this->layout();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aVeiculo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar veículos.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('veiculos') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $placa = strtoupper($this->input->post('placa'));

            if ($this->veiculos_model->placaExists($placa)) {
                $this->data['custom_error'] = '<div class="form_error"><p>Esta placa já está cadastrada.</p></div>';
            } else {
                $data = [
                    'placa' => $placa,
                    'modelo' => $this->input->post('modelo'),
                    'ano' => $this->input->post('ano'),
                    'cor' => $this->input->post('cor'),
                    'km' => $this->input->post('km') ?: null,
                    'chassi' => $this->input->post('chassi'),
                    'clientes_id' => $this->input->post('clientes_id') ?: null,
                    'dataCadastro' => date('Y-m-d'),
                ];

                if (is_numeric($id = $this->veiculos_model->add('veiculos', $data))) {
                    log_info('Adicionou um veículo. ID: ' . $id);
                    $this->session->set_flashdata('success', 'Veículo adicionado com sucesso!');
                    redirect(site_url('veiculos/editar/') . $id);
                } else {
                    $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
                }
            }
        }

        $this->data['view'] = 'veiculos/adicionarVeiculo';

        return $this->layout();
    }

    public function editar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3)) || ! $this->veiculos_model->getById($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Veículo não encontrado ou parâmetro inválido.');
            redirect('veiculos/gerenciar');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eVeiculo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar veículos.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('veiculos') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $idVeiculo = $this->input->post('idVeiculos');
            $placa = strtoupper($this->input->post('placa'));

            if ($this->veiculos_model->placaExists($placa, $idVeiculo)) {
                $this->data['custom_error'] = '<div class="form_error"><p>Esta placa já está sendo utilizada por outro veículo.</p></div>';
            } else {
                $data = [
                    'placa' => $placa,
                    'modelo' => $this->input->post('modelo'),
                    'ano' => $this->input->post('ano'),
                    'cor' => $this->input->post('cor'),
                    'km' => $this->input->post('km') ?: null,
                    'chassi' => $this->input->post('chassi'),
                    'clientes_id' => $this->input->post('clientes_id') ?: null,
                ];

                if ($this->veiculos_model->edit('veiculos', $data, 'idVeiculos', $idVeiculo) == true) {
                    $this->session->set_flashdata('success', 'Veículo editado com sucesso!');
                    log_info('Alterou um veículo. ID: ' . $idVeiculo);
                    redirect(site_url('veiculos/editar/') . $idVeiculo);
                } else {
                    $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
                }
            }
        }

        $this->data['result'] = $this->veiculos_model->getById($this->uri->segment(3));
        $this->data['view'] = 'veiculos/editarVeiculo';

        return $this->layout();
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vVeiculo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar veículos.');
            redirect(base_url());
        }

        $this->data['custom_error'] = '';
        $this->data['result'] = $this->veiculos_model->getById($this->uri->segment(3));
        $this->data['results'] = $this->veiculos_model->getOsByVeiculo($this->uri->segment(3));
        $this->data['view'] = 'veiculos/visualizar';

        return $this->layout();
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dVeiculo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir veículos.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir veículo.');
            redirect(site_url('veiculos/gerenciar/'));
        }

        $os = $this->veiculos_model->getOsByVeiculo($id);
        if ($os) {
            $this->session->set_flashdata('error', 'Não é possível excluir este veículo pois existem Ordens de Serviço vinculadas a ele.');
            redirect(site_url('veiculos/gerenciar/'));
        }

        $this->veiculos_model->delete('veiculos', 'idVeiculos', $id);
        log_info('Removeu um veículo. ID: ' . $id);

        $this->session->set_flashdata('success', 'Veículo excluído com sucesso!');
        redirect(site_url('veiculos/gerenciar/'));
    }
}
