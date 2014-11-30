<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConfigService
 *
 * @author ssrp
 */
require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

class ConfigService {

    protected $databaseHandler;
    
    protected function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }

    public function createDatabaseTables() {
        $this->getDatabaseHandler()->executeQuery($this->getCreateCdmeTablesQuery());
        $this->createDbInformationFile();
    }

    protected function createDbInformationFile() {
        $dbInfoFile = fopen(dirname(__FILE__) . '/../Config/db_details.txt', 'w');
    }

    protected function getCreateCdmeTablesQuery(){
        return file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_tables.sql');
    }
}
