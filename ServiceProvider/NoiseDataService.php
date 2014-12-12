<?php

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
require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

class NoiseDataService {

    protected $rawData;
    protected $databaseHandler;

    public function __construct() {
        
    }

    protected function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }

    protected function createDataUploadQuery($rawData) {
        return "INSERT INTO `cdme_noise_data` (`location_id`, `noise_level`) VALUES (" . $rawData['location_id'] . ", " . $rawData['noise_level'] . ")";
    }

    protected function createDataDownloadQuery($rawData) {
        return "SELECT * FROM `cdme_noise_data` ORDER BY `date_time` ASC;";
    }

    public function initializeDataUploadService($rawData) {
        $this->setRawData($rawData);
        $uploadQuery = $this->createDataUploadQuery($rawData);
        $results = $this->getDatabaseHandler()->executeQuery($uploadQuery);
        return json_encode($results);
    }

    public function initializeDataDownloadService($rawData) {
        $this->setRawData($rawData);
        $downloadQuery = $this->createDataDownloadQuery($rawData);
        $results = $this->getDatabaseHandler()->executeQuery($downloadQuery);
        return json_encode($results);
    }

    function getRawData() {
        return $this->rawData;
    }

    function setRawData($rawData) {
        $this->rawData = $rawData;
    }

}
