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
require_once dirname(__FILE__) . '/BaseCdmeService.php';

class NoiseDataService extends BaseCdmeService {

    public function isExistingLocation($longitude, $latitude) {
        $existingLocationIdQuery = 'SELECT `id` FROM `cdme_location` WHERE `longitude`=' . $longitude . " AND `latitude`=" . $latitude . ' LIMIT 1;';
        $existingLocationIdArray = $this->getDatabaseHandler()->executeQuery($existingLocationIdQuery);
        if (!empty($existingLocationIdArray)) {
            return $existingLocationIdArray[0]['id'];
        } else {
            return null;
        }
    }

    public function isExistingUser($imei) {
        $existingUserIdQuery = "SELECT `id` FROM `cdme_user` WHERE `imei` ='" . $imei . "' LIMIT 1;";
        $existingUserIdArray = $this->getDatabaseHandler()->executeQuery($existingUserIdQuery);
        if (!empty($existingUserIdArray)) {
            return $existingUserIdArray[0]['id'];
        } else {
            return null;
        }
    }

    public function getReverseGeoCodingLocationDetails($latitude, $longitude) {
        $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&sensor=true";
        $data = @file_get_contents($url);
        $jsondata = json_decode($data, true);
        if (is_array($jsondata) && $jsondata['status'] == "OK") {
            $location = array();
            foreach ($jsondata["results"] as $result) {
                foreach ($result['address_components'] as $component) {
                    switch ($component['types']) {
                        case in_array('street_number', $component['types']):
                            $location['street_number'] = $component['long_name'];
                            break;
                        case in_array('route', $component['types']):
                            $location['street'] = $component['long_name'];
                            break;
                        case in_array('sublocality', $component['types']):
                            $location['sublocality'] = $component['long_name'];
                            break;
                        case in_array('locality', $component['types']):
                            $location['locality'] = $component['long_name'];
                            break;
                        case in_array('administrative_area_level_2', $component['types']):
                            $location['admin_2'] = $component['long_name'];
                            break;
                        case in_array('administrative_area_level_1', $component['types']):
                            $location['admin_1'] = $component['long_name'];
                            break;
                        case in_array('postal_code', $component['types']):
                            $location['postal_code'] = $component['long_name'];
                            break;
                        case in_array('country', $component['types']):
                            $location['country'] = $component['long_name'];
                            break;
                    }
                }
            }
        }
        return $location;
    }

    public function obtainAdmin2RegionIdByLatLong($latitude, $longitude) {
        $locationDetailsArray = $this->getReverseGeoCodingLocationDetails($latitude, $longitude);
        $admin2RegionName = $locationDetailsArray['admin_2'];
        return $this->getAdmin2RegionIdByName($admin2RegionName);
    }

    public function obtainAdmin1RegionIdByLatLong($latitude, $longitude) {
        $locationDetailsArray = $this->getReverseGeoCodingLocationDetails($latitude, $longitude);
        $admin1RegionName = $locationDetailsArray['admin_1'];
        return $this->getAdmin1RegionIdByName($admin1RegionName);
    }

    public function getAdmin2RegionIdByName($admin2RegionName) {
        $getAdmin2RegionIdQuery = 'SELECT `id` FROM `cdme_admin_2_region` WHERE `name`=\'' . trim($admin2RegionName) . '\'';
        $result = $this->getDatabaseHandler()->executeQuery($getAdmin2RegionIdQuery);
        return $result[0]['id'];
    }

    public function getAdmin1RegionIdByName($admin1RegionName) {
        $getAdmin1RegionIdQuery = 'SELECT `id` FROM `cdme_admin_1_region` WHERE `name`=\'' . trim($admin1RegionName) . '\'';
        $result = $this->getDatabaseHandler()->executeQuery($getAdmin1RegionIdQuery);
        return $result[0]['id'];
    }

    public function saveCurrentLocation($longitude, $latitude) {
        $admin2RegionId = $this->obtainAdmin2RegionIdByLatLong($latitude, $longitude);
        $admin1RegionId = $this->obtainAdmin1RegionIdByLatLong($latitude, $longitude);
        if (!empty($admin1RegionId) && !empty($admin2RegionId)) {
            $locationInsertQuery = "INSERT INTO `cdme_location` (`longitude`, `latitude`, `admin_2_region_id`,`admin_1_region_id`) VALUES (" . $longitude . "," . $latitude . "," . $admin2RegionId . "," . $admin1RegionId . ");";
            $this->getDatabaseHandler()->executeQuery($locationInsertQuery);
            return true;
        } else {
            return false;
        }
    }

    public function saveCurrentUser($imei) {
        $userInsertQuery = "INSERT INTO `cdme_user` (`imei`) VALUES ('" . $imei . "');";
        $this->getDatabaseHandler()->executeQuery($userInsertQuery);
        return true;
    }

    protected function createDataUploadQuery($data) {
        $rawData = $data['rawdata'];
        $metaData = $data['metadata'];
        switch ($metaData['feature']) {
            case 'noiseData':
                $locationId = $this->isExistingLocation($rawData['longitude'], $rawData['latitude']);
                $userId = $this->isExistingUser($metaData['imei']);
                if (!$locationId) {
                    $result = $this->saveCurrentLocation($rawData['longitude'], $rawData['latitude']);
                    $locationId = $this->isExistingLocation($rawData['longitude'], $rawData['latitude']);
                }
                if (!$userId) {
                    $result = $this->saveCurrentUser($metaData['imei']);
                    $userId = $this->isExistingUser($metaData['imei']);
                }
                $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES ('" . $userId . "','" . $locationId . "', '" . $rawData['noise_level'] . "','" . $rawData['date_time'] . "');";
                break;
        }
        return $dataInsertQuery;
    }

    public function createDataDownloadQuery($data) {
        $metaData = $data['metadata'];
        $rawData = $data['rawdata'];
        switch ($metaData['feature']) {
            case 'allLocations':
                $query = "SELECT * FROM `cdme_noise_data` LEFT JOIN `cdme_location` ON `cdme_noise_data`.`location_id` = `cdme_location`.`id` ORDER BY `cdme_noise_data`.`date_time` ASC;";
                break;
            case 'adminRegionStatistics':
                $query = 'SELECT * FROM `cdme_admin_' . $rawData['region_level'] . '_region_statistics` WHERE `region_id`=' . $rawData['region_id'] . ';';
                break;
            case 'latestLocationStatistics':
                $query = 'SELECT p1.*
                            FROM `cdme_location_point_statistics` p1
                            INNER JOIN
                            (
                                SELECT max(`date_time`) MaxDateTime, `location_id`
                                FROM `cdme_location_point_statistics`
                                GROUP BY `location_id`
                            ) p2
                            ON p1.`location_id` = p2.`location_id`
                            AND p1.`date_time` = p2.MaxDateTime
                            LEFT JOIN `cdme_location` r ON r.`id` = p1.`location_id`
                            WHERE p1.`location_id` = ' . $rawData['location_id'] . '
                            ORDER BY p1.`location_id`;';
                break;
            case 'overallLocationStatistics':
                $query = 'SELECT * FROM `cdme_location_point_statistics` WHERE `location_id` = ' . $rawData['location_id'] . ' ORDER BY `date_time`;';
                break;
        }
        return $query;
    }

    public function initializeDataUploadService($data) {
        $uploadQuery = $this->createDataUploadQuery($data);
        $results = $this->getDatabaseHandler()->executeQuery($uploadQuery);
        return json_encode($results);
    }

    public function initializeDataDownloadService($data) {
        $downloadQuery = $this->createDataDownloadQuery($data);
        $results = $this->getDatabaseHandler()->executeQuery($downloadQuery);
        return json_encode($results);
    }

}
