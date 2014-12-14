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

    public function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }

    public function isExistingLocation($longitude, $latitude) {
        $existingLocationIdQuery = 'SELECT `id` FROM `cdme_location` WHERE `longitude`=' . $longitude . " AND `latitude`=" . $latitude . ' LIMIT 1;';
        $existingLocationIdArray = $this->getDatabaseHandler()->executeQuery($existingLocationIdQuery);
        if (!empty($existingLocationIdArray)) {
            return $existingLocationIdArray[0]['id'];
        } else {
            return null;
        }
    }

    public function saveCurrentLocation($longitude, $latitude) {
        $locationInsertQuery = "INSERT INTO `cdme_location` (`longitude`, `latitude`) VALUES (" . $longitude . "," . $latitude . ");";
        $this->getDatabaseHandler()->executeQuery($locationInsertQuery);
    }

    protected function createDataUploadQuery($rawData) {
        $existingLocationId = $this->isExistingLocation($rawData['longitude'], $rawData['latitude']);
        if ($existingLocationId) {
            $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (NULL,'" . $existingLocationId . "', '" . $rawData['noise_level'] . "','" . $rawData['date_time'] . "');";
        } else {
            $this->saveCurrentLocation($rawData['longitude'], $rawData['latitude']);
            $newLocationId = $this->isExistingLocation($rawData['longitude'], $rawData['latitude']);
            $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (NULL, '" . $newLocationId . "', '" . $rawData['noise_level'] . "','" . $rawData['date_time'] . "');";
        }
        return $dataInsertQuery;
    }

    public function createDataDownloadQuery($rawData) {
        return "SELECT * FROM `cdme_noise_data` LEFT JOIN `cdme_location` ON `cdme_noise_data`.`location_id` = `cdme_location`.`id` ORDER BY `cdme_noise_data`.`date_time` ASC;";
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
