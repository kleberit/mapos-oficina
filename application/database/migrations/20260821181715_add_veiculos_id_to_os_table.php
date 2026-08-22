<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_veiculos_id_to_os_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('os', [
            'veiculos_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'clientes_id',
            ],
        ]);

        $this->db->query('ALTER TABLE `os` ADD INDEX `fk_os_veiculos1_idx` (`veiculos_id` ASC)');
        $this->db->query('ALTER TABLE `os` ADD CONSTRAINT `fk_os_veiculos1`
            FOREIGN KEY (`veiculos_id`)
            REFERENCES `veiculos` (`idVeiculos`)
            ON DELETE SET NULL
            ON UPDATE NO ACTION
        ');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `os` DROP FOREIGN KEY `fk_os_veiculos1`');
        $this->db->query('ALTER TABLE `os` DROP INDEX `fk_os_veiculos1_idx`');
        $this->dbforge->drop_column('os', 'veiculos_id');
    }
}
