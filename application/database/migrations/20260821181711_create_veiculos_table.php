<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_create_veiculos_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'idVeiculos' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'placa' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'modelo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'ano' => [
                'type' => 'VARCHAR',
                'constraint' => 9,
                'null' => true,
            ],
            'cor' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'km' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'chassi' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'clientes_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'dataCadastro' => [
                'type' => 'DATE',
                'null' => true,
            ],
        ]);
        $this->dbforge->add_key('idVeiculos', true);
        $this->dbforge->create_table('veiculos', true);

        $this->db->query('ALTER TABLE `veiculos` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `veiculos` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        $this->db->query('ALTER TABLE `veiculos` ADD UNIQUE INDEX `uq_veiculos_placa` (`placa` ASC)');

        $this->db->query('ALTER TABLE `veiculos` ADD INDEX `fk_veiculos_clientes1_idx` (`clientes_id` ASC)');
        $this->db->query('ALTER TABLE `veiculos` ADD CONSTRAINT `fk_veiculos_clientes1`
            FOREIGN KEY (`clientes_id`)
            REFERENCES `clientes` (`idClientes`)
            ON DELETE SET NULL
            ON UPDATE NO ACTION
        ');
    }

    public function down()
    {
        $this->dbforge->drop_table('veiculos');
    }
}
