<?php

class Veiculos_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $one = false)
    {
        $this->db->select($fields);
        $this->db->from($table);
        $this->db->order_by('idVeiculos', 'desc');
        $this->db->limit($perpage, $start);
        if ($where) {
            $this->db->like('placa', $where);
            $this->db->or_like('modelo', $where);
            $this->db->or_like('chassi', $where);
        }

        $query = $this->db->get();

        $result = ! $one ? $query->result() : $query->row();

        return $result;
    }

    public function getById($id)
    {
        $this->db->where('idVeiculos', $id);
        $this->db->limit(1);

        return $this->db->get('veiculos')->row();
    }

    public function getByPlaca($placa)
    {
        $this->db->where('placa', $placa);
        $this->db->limit(1);

        return $this->db->get('veiculos')->row();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            return $this->db->insert_id($table);
        }

        return false;
    }

    public function edit($table, $data, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    public function delete($table, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->delete($table);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function count($table)
    {
        return $this->db->count_all($table);
    }

    /**
     * Verifica se a placa já existe na tabela de veículos
     *
     * @param  string  $placa
     * @param  int     $id (opcional, para excluir o próprio veículo na edição)
     * @return bool
     */
    public function placaExists($placa, $id = null)
    {
        $this->db->where('placa', strtoupper($placa));

        if ($id !== null) {
            $this->db->where('idVeiculos !=', $id);
        }

        $query = $this->db->get('veiculos');

        return $query->num_rows() > 0;
    }

    /**
     * Retorna todas as OS já feitas nesse veículo, independente do cliente
     * que trouxe o veículo em cada uma (histórico por veículo)
     *
     * @param  int  $id
     * @return array
     */
    public function getOsByVeiculo($id)
    {
        $this->db->select('os.*, clientes.nomeCliente');
        $this->db->from('os');
        $this->db->join('clientes', 'clientes.idClientes = os.clientes_id');
        $this->db->where('os.veiculos_id', $id);
        $this->db->order_by('os.idOs', 'desc');

        return $this->db->get()->result();
    }

    /**
     * Autocomplete de veículo por placa (e modelo), usado na tela de O.S.
     *
     * @param  string  $q
     */
    public function autoCompleteVeiculo($q)
    {
        $this->db->select('*');
        $this->db->limit(25);
        $this->db->like('placa', $q);
        $this->db->or_like('modelo', $q);
        $query = $this->db->get('veiculos');

        if ($query->num_rows() > 0) {
            $row_set = [];
            foreach ($query->result_array() as $row) {
                $label = $row['placa'] . ' - ' . $row['modelo'];
                if ($row['ano'] || $row['cor']) {
                    $label .= ' (' . trim($row['ano'] . ($row['ano'] && $row['cor'] ? '/' : '') . $row['cor']) . ')';
                }

                $row_set[] = [
                    'label' => $label,
                    'id' => $row['idVeiculos'],
                    'placa' => $row['placa'],
                    'modelo' => $row['modelo'],
                ];
            }
            echo json_encode($row_set);
        }
    }
}
