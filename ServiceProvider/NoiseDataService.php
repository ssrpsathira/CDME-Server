<?php

require_once dirname(__FILE__).'/../Engine/DatabaseHandler.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NoiseDataService
 *
 * @author ssrp
 */
class NoiseDataService {

    protected $rawData;
    protected $databaseHandler;
    
    public function __construct() {
        $createNoiseTablesQuery = file_get_contents(dirname(__FILE__).'/../DbScripts/noise_service_tables.sql');
        $this->getDatabaseHandler()->executeQuery($createNoiseTablesQuery);
    }

    protected function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }
    
    protected function createDataUploadQuery($rawData){
         return "INSERT INTO `cdme_noise_data` (`location_id`, `noise_level`) VALUES (".$rawData['location_id'].", ".$rawData['noise_level'].")";
    }

    public function initializeDataUploadService($rawData) {
        $this->setRawData($rawData);
        $uploadQuery = $this->createDataUploadQuery($rawData);
        $results = $this->getDatabaseHandler()->executeQuery($uploadQuery);
        return $results;
    }

    public function initializeDataDownloadService($rawData) {
        
    }

    function getRawData() {
        return $this->rawData;
    }

    function setRawData($rawData) {
        $this->rawData = $rawData;
    }

}
