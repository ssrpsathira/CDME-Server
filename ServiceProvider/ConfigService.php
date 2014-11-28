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
    protected $createNoiseDatabaseQuery;
    protected $createNoiseTablesQuery;

    protected function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }

    public function __construct() {
        $this->createNoiseTablesQuery = file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_tables.sql');
    }

    public function createDatabaseAndTables() {
        $this->getDatabaseHandler()->createDatabase();
        $this->getDatabaseHandler()->executeQuery($this->createNoiseTablesQuery);
        $this->createDbInformationFile();
    }

    protected function createDbInformationFile() {
        $dbInfoFile = fopen(dirname(__FILE__) . '/../Config/db_details.txt', 'w');
    }

}
