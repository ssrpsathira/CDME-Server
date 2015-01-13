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

    public function initializeDataUploadService($data) {
        $uploadQuery = $this->createDataUploadQuery($data);
        $results = $this->getDatabaseHandler()->executeQuery($uploadQuery);
        return json_encode($results);
    }

    public function initializeDataDownloadService($data) {
        $results = $this->getFeatureWiseData($data);
        return json_encode($results);
    }

    public function generateAdmin2RegionKmzLayer($param, $imei) {
        $fromDate = ($param['from_date']) ? strtotime($param['from_date']) : 0;
        $toDate = ($param['to_date']) ? strtotime($param['to_date']) : 0;
        $timeStamp = $param['time_stamp'];

        if ($fromDate == 0 && $toDate == 0) {
            $overallDataQuery = "SELECT r2.`id`, r2.`name`, n.`noise_level` FROM `cdme_admin_2_region` r2 LEFT JOIN `cdme_location` l ON l.`admin_2_region_id` = r2.`id` LEFT JOIN `cdme_noise_data` n ON n.`location_id` = l.`id`;";
        } elseif ($fromDate == 0 && $toDate != 0) {
            $overallDataQuery = "SELECT r2.`id`, r2.`name`, n.`noise_level` FROM `cdme_admin_2_region` r2 LEFT JOIN `cdme_location` l ON l.`admin_2_region_id` = r2.`id` LEFT JOIN `cdme_noise_data` n ON n.`location_id` = l.`id` AND n.`date_time` <= $toDate ;";
        } elseif ($fromDate != 0 && $toDate == 0) {
            $overallDataQuery = "SELECT r2.`id`, r2.`name`, n.`noise_level` FROM `cdme_admin_2_region` r2 LEFT JOIN `cdme_location` l ON l.`admin_2_region_id` = r2.`id` LEFT JOIN `cdme_noise_data` n ON n.`location_id` = l.`id` AND n.`date_time` >= $fromDate ;";
        } elseif ($fromDate != 0 && $toDate != 0) {
            $overallDataQuery = "SELECT r2.`id`, r2.`name`, n.`noise_level` FROM `cdme_admin_2_region` r2 LEFT JOIN `cdme_location` l ON l.`admin_2_region_id` = r2.`id` LEFT JOIN `cdme_noise_data` n ON n.`location_id` = l.`id` AND (n.`date_time` >= $fromDate AND n.`date_time` <= $toDate);";
        }
        $result = $this->getDatabaseHandler()->executeQuery($overallDataQuery);
        foreach ($result as $set) {
            if (empty($regionNoiseArray[$set['id']])) {
                $regionNoiseArray[$set['id']] = array();
            }
            $regionNoiseArray[$set['id']] = array_merge($regionNoiseArray[$set['id']], array($set['noise_level']));
            $regionIdToNameArray[$set['id']] = $set['name'];
        }
        $kmlStyleText = '';
        $placeMarkText = '';
        foreach ($regionNoiseArray as $regionId => $regionNoiseValues) {
            $regionId = $regionId;
            sort($regionNoiseValues);
            $mean = array_sum($regionNoiseValues) / count($regionNoiseValues);

            $ar = array_replace($regionNoiseValues, array_fill_keys(array_keys($regionNoiseValues, null), ''));
            $count = array_count_values($ar);
            $mod = array_search(max($count), $count);

            $middle = round(count($regionNoiseValues) / 2);
            $median = $regionNoiseValues[$middle - 1];

            $variance = 0.0;
            $size = 0;
            foreach ($regionNoiseValues as $i) {
                if ($i != null) {
                    $size += 1;
                }
                $variance += pow($i - $mean, 2);
            }
            if ($size > 1) {
                $sd = (float) sqrt($variance) / sqrt($size);
            } else {
                $sd = 0;
            }

            $polygonOpacity = '55';
            $portionSize = round(240 / 120, 0, PHP_ROUND_HALF_DOWN);
            $iH = 240 - ($mean) * $portionSize;
            $iS = 100;
            $iV = 100;

            $dS = $iS / 100.0; // Saturation: 0.0-1.0
            $dV = $iV / 100.0; // Lightness:  0.0-1.0
            $dC = $dV * $dS;   // Chroma:     0.0-1.0
            $dH = $iH / 60.0;  // H-Prime:    0.0-6.0
            $dT = $dH;       // Temp variable

            while ($dT >= 2.0)
                $dT -= 2.0; // php modulus does not work with float
            $dX = $dC * (1 - abs($dT - 1));     // as used in the Wikipedia link

            switch ($dH) {
                case($dH >= 0.0 && $dH < 1.0):
                    $dR = $dC;
                    $dG = $dX;
                    $dB = 0.0;
                    break;
                case($dH >= 1.0 && $dH < 2.0):
                    $dR = $dX;
                    $dG = $dC;
                    $dB = 0.0;
                    break;
                case($dH >= 2.0 && $dH < 3.0):
                    $dR = 0.0;
                    $dG = $dC;
                    $dB = $dX;
                    break;
                case($dH >= 3.0 && $dH < 4.0):
                    $dR = 0.0;
                    $dG = $dX;
                    $dB = $dC;
                    break;
                case($dH >= 4.0 && $dH < 5.0):
                    $dR = $dX;
                    $dG = 0.0;
                    $dB = $dC;
                    break;
                case($dH >= 5.0 && $dH < 6.0):
                    $dR = $dC;
                    $dG = 0.0;
                    $dB = $dX;
                    break;
                default:
                    $dR = 0.0;
                    $dG = 0.0;
                    $dB = 0.0;
                    break;
            }

            $dM = $dV - $dC;
            $dR += $dM;
            $dG += $dM;
            $dB += $dM;
            $dR *= 255;
            $dG *= 255;
            $dB *= 255;

            $R = dechex(round($dR));
            If (strlen($R) < 2)
                $R = '0' . $R;

            $G = dechex(round($dG));
            If (strlen($G) < 2)
                $G = '0' . $G;

            $B = dechex(round($dB));
            If (strlen($B) < 2)
                $B = '0' . $B;

            $polygonColor = $B . $G . $R;
            $regionName = $regionIdToNameArray[$regionId];

            $kmlStyleText .= str_replace(array('%region_id%', '%poligon_opacity%', '%poligon_color%'), array($regionId, $polygonOpacity, $polygonColor), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_style.txt'));

            $description = "<table style=\"width:100%\">
                        <tr>
                            <td>Mean</td>
                            <td>$mean</td> 
                        </tr>
                        <tr>
                            <td>Median</td>
                            <td>$median</td> 
                        </tr>
                        <tr>
                            <td>Mod</td>
                            <td>$mod</td> 
                        </tr>
                        <tr>
                            <td>Standard Deviation</td>
                            <td>$sd</td> 
                        </tr>
                    </table>";

            $polygonsText = file_get_contents(dirname(__FILE__) . '/../kml/SL/polygons/level2/region_' . $regionId . '_polygons.txt');
            $placeMarkText .= str_replace(array('%region_level%', '%region_name%', '%description%', '%region_id%', '%polygons%'), array(2, $regionName, $description, $regionId, $polygonsText), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_placemark.txt'));
        }
        $kmlText = str_replace(array('{styles}', '{placemarks}'), array($kmlStyleText, $placeMarkText), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_template.txt'));

        foreach (glob(dirname(__FILE__) . '/../kml/SL/admin_2_regions_' . $imei . '*') as $filename) {
            unlink($filename);
        }
        $path = dirname(__FILE__) . '/../kml/SL/admin_2_regions_' . $imei . '_' . $timeStamp . '.kml';
        $zipArchivePath = dirname(__FILE__) . '/../kml/SL/admin_2_regions_' . $imei . '_' . $timeStamp . '.zip';
        $kmzArchivePath = dirname(__FILE__) . '/../kml/SL/admin_2_regions_' . $imei . '_' . $timeStamp . '.kmz';

        file_put_contents($path, $kmlText);

        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zipArchivePath, ZIPARCHIVE::CREATE) != TRUE) {
            die("Could not open archive");
        }
        $zipArchive->addFile($path, 'admin_2_regions_' . $imei . '_' . $timeStamp . '.kml');
// close and save archive
        $zipArchive->close();
        rename($zipArchivePath, $kmzArchivePath);
        return 'success';
    }

    public function getAdminRegionStatistics($rawData) {
        $fromDate = ($rawData['from_date']) ? strtotime($rawData['from_date']) : 0;
        $toDate = ($rawData['to_date']) ? strtotime($rawData['to_date']) : 0;
        $samplingRate = ($rawData['sampling_rate']) ? $rawData['sampling_rate'] : '1 day';
        if ($fromDate == 0 && $toDate == 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];

            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        } elseif ($fromDate == 0 && $toDate != 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];
        } elseif ($fromDate != 0 && $toDate == 0) {
            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        }
        $incrementedDate = strtotime(date('Y-m-d H:i:s', $fromDate) . ' + ' . $samplingRate);
        $statisticsArray = array();
        while ($incrementedDate <= $toDate) {
            $newFromDate = strtotime(date('Y-m-d H:i:s', $incrementedDate) . ' - ' . $samplingRate);
            $noiseDataQuery = "SELECT r2.`id`, r2.`name`, n.`noise_level` FROM `cdme_admin_" . $rawData['region_level'] . "_region` r2 LEFT JOIN `cdme_location` l ON l.`admin_2_region_id` = r2.`id` LEFT JOIN `cdme_noise_data` n ON n.`location_id` = l.`id` AND (n.`date_time` >= $newFromDate AND n.`date_time` <= $incrementedDate) WHERE r2.`id` = " . $rawData['region_id'] . ";";
            $result = $this->getDatabaseHandler()->executeQuery($noiseDataQuery);
            $noiseLevels = array();
            foreach ($result as $set) {
                if ($set['noise_level']) {
                    $noiseLevels[] = $set['noise_level'];
                }
            }
            if (count($noiseLevels) > 0) {
                sort($noiseLevels);
                $mean = array_sum($noiseLevels) / count($noiseLevels);

                $ar = array_replace($noiseLevels, array_fill_keys(array_keys($noiseLevels, null), ''));
                $count = array_count_values($ar);
                $mod = array_search(max($count), $count);

                $middle = round(count($noiseLevels) / 2);
                $median = $noiseLevels[$middle - 1];

                $variance = 0.0;
                $size = 0;
                foreach ($noiseLevels as $i) {
                    if ($i != null) {
                        $size += 1;
                    }
                    $variance += pow($i - $mean, 2);
                }
                if ($size > 1) {
                    $sd = (float) sqrt($variance) / sqrt($size);
                } else {
                    $sd = 0;
                }
            } else {
                $mean = 0;
                $median = null;
                $mod = null;
                $sd = 0;
            }
            $statisticsArray[] = array('date_time' => $incrementedDate, 'mean' => $mean, 'median' => $median, 'mod' => $mod, 'sd' => $sd);
            $incrementedDate = strtotime(date('Y-m-d H:i:s', $incrementedDate) . ' + ' . $samplingRate);
        }
        return $statisticsArray;
    }

    public function downloadAllLocationPoints($rawData) {
        $fromDate = ($rawData['from_date']) ? strtotime($rawData['from_date']) : 0;
        $toDate = ($rawData['to_date']) ? strtotime($rawData['to_date']) : 0;
        if ($fromDate == 0 && $toDate == 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];

            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        } elseif ($fromDate == 0 && $toDate != 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];
        } elseif ($fromDate != 0 && $toDate == 0) {
            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        }
        $locationQuery = "SELECT * FROM `cdme_noise_data` n LEFT JOIN `cdme_location` l ON n.`location_id` = l.`id` WHERE n.`date_time` >= $fromDate AND n.`date_time` <= $toDate ORDER BY n.`date_time` ASC;";
        return $this->getDatabaseHandler()->executeQuery($locationQuery);
    }

    public function getLocationStatistics($rawData) {
        $locationId = $rawData['location_id'];
        $fromDate = ($rawData['from_date']) ? strtotime($rawData['from_date']) : 0;
        $toDate = ($rawData['to_date']) ? strtotime($rawData['to_date']) : 0;
        $displacementRange = $rawData['displacement_range'];
        if ($fromDate == 0 && $toDate == 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];

            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        } elseif ($fromDate == 0 && $toDate != 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];
        } elseif ($fromDate != 0 && $toDate == 0) {
            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        }
        $allLocationQuery = "SELECT * FROM `cdme_location`;";
        $specificLocationQuery = "SELECT * FROM `cdme_location` WHERE `id` = $locationId";
        $result = $this->getDatabaseHandler()->executeQuery($specificLocationQuery);
        $specificLocation = $result[0];
        $closeLocationArray[] = $specificLocation;
        $result = $this->getDatabaseHandler()->executeQuery($allLocationQuery);
        foreach ($result as $location) {
            $distance = $this->getDisplacement(array('lat' => $specificLocation['latitude'], 'long' => $specificLocation['longitude']), array('lat' => $location['latitude'], 'long' => $location['longitude']));
            if ($distance <= $displacementRange && $location != $specificLocation) {
                $closeLocationArray[] = $location;
            }
        }
        $noiseLevelArray = array();
        foreach ($closeLocationArray as $location) {
            $id = $location['id'];
            $locationNoiseQuery = "SELECT n.`noise_level` FROM `cdme_noise_data` n LEFT JOIN `cdme_location` l ON l.`id` = n.`location_id` WHERE l.`id` = $id AND (n.`date_time` >= $fromDate AND n.`date_time` <= $toDate);";
            $result = $this->getDatabaseHandler()->executeQuery($locationNoiseQuery);
            foreach ($result as $noiseLevel) {
                $noiseLevelArray[] = $noiseLevel['noise_level'];
            }
        }
        if (count($noiseLevelArray) > 0) {
            sort($noiseLevelArray);
            $mean = array_sum($noiseLevelArray) / count($noiseLevelArray);

            $ar = array_replace($noiseLevelArray, array_fill_keys(array_keys($noiseLevelArray, null), ''));
            $count = array_count_values($ar);
            $mod = array_search(max($count), $count);

            $middle = round(count($noiseLevelArray) / 2);
            $median = $noiseLevelArray[$middle - 1];

            $variance = 0.0;
            $size = 0;
            foreach ($noiseLevelArray as $i) {
                if ($i != null) {
                    $size += 1;
                }
                $variance += pow($i - $mean, 2);
            }
            if ($size > 1) {
                $sd = (float) sqrt($variance) / sqrt($size);
            } else {
                $sd = 0;
            }
        } else {
            $mean = 0;
            $median = null;
            $mod = null;
            $sd = 0;
        }
        return array('mean' => $mean, 'median' => $median, 'mod' => $mod, 'sd' => $sd);
    }

    public function getLocationNoiseValues($rawData) {
        $locationId = $rawData['location_id'];
        $fromDate = ($rawData['from_date']) ? strtotime($rawData['from_date']) : 0;
        $toDate = ($rawData['to_date']) ? strtotime($rawData['to_date']) : 0;
        $displacementRange = $rawData['displacement_range'];
        if ($fromDate == 0 && $toDate == 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];

            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        } elseif ($fromDate == 0 && $toDate != 0) {
            $minFromDateaQuery = "SELECT `id`, MIN(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($minFromDateaQuery);
            $fromDate = $result[0][1];
        } elseif ($fromDate != 0 && $toDate == 0) {
            $maxToDateQuery = "SELECT `id`, MAX(`date_time`) FROM `cdme_noise_data`;";
            $result = $this->getDatabaseHandler()->executeQuery($maxToDateQuery);
            $toDate = $result[0][1];
        }
        $allLocationQuery = "SELECT * FROM `cdme_location`;";
        $specificLocationQuery = "SELECT * FROM `cdme_location` WHERE `id` = $locationId";
        $result = $this->getDatabaseHandler()->executeQuery($specificLocationQuery);
        $specificLocation = $result[0];
        $closeLocationArray[] = $specificLocation;
        $result = $this->getDatabaseHandler()->executeQuery($allLocationQuery);
        foreach ($result as $location) {
            $distance = $this->getDisplacement(array('lat' => $specificLocation['latitude'], 'long' => $specificLocation['longitude']), array('lat' => $location['latitude'], 'long' => $location['longitude']));
            if ($distance <= $displacementRange && $location != $specificLocation) {
                $closeLocationArray[] = $location;
            }
        }
        $noiseLevelDistributionArray = array();
        foreach ($closeLocationArray as $location) {
            $id = $location['id'];
            $locationNoiseQuery = "SELECT n.`date_time`, n.`noise_level` FROM `cdme_noise_data` n LEFT JOIN `cdme_location` l ON l.`id` = n.`location_id` WHERE l.`id` = $id AND (n.`date_time` >= $fromDate AND n.`date_time` <= $toDate);";
            $result = $this->getDatabaseHandler()->executeQuery($locationNoiseQuery);
            foreach ($result as $noiseLevel) {
                $noiseLevelDistributionArray[] = array('date_time' => $noiseLevel['date_time'], 'noise_level' => $noiseLevel['noise_level']);
            }
        }
        return $noiseLevelDistributionArray;
    }

    public function getFeatureWiseData($data) {
        $metaData = $data['metadata'];
        $rawData = $data['rawdata'];
        switch ($metaData['feature']) {
            case 'admin2kmzLayer':
                $results = $this->generateAdmin2RegionKmzLayer($rawData, $metaData['imei']);
                break;
            case 'allLocations':
                $results = $this->downloadAllLocationPoints($rawData);
                break;
            case 'adminRegionStatistics':
                $results = $this->getAdminRegionStatistics($rawData);
                break;
            case 'latestLocationStatistics':
                $results = $this->getLocationStatistics($rawData);
                break;
            case 'overallLocationStatistics':
                $results = $this->getLocationNoiseValues($rawData);
                break;
        }
        return $results;
    }

}
