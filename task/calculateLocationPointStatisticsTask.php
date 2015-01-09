<?php

$clusterRange = 10;
require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';
require_once dirname(__FILE__) . '/../ServiceProvider/BaseCdmeService.php';
$dbHandler = new DatabaseHandler();
$baseService = new BaseCdmeService();

$allNoiseDataQuery = "SELECT n.`id`,n.`noise_level`,n.`date_time`,n.`location_id`,l.`longitude`,l.`latitude` FROM `cdme_noise_data` n LEFT JOIN `cdme_location` l ON l.`id` = n.`location_id`;";
$allNoiseData = $dbHandler->executeQuery($allNoiseDataQuery);

$clusteredLocations = array();
$addedLocationsArray = array();

for ($i = 0; $i < count($allNoiseData); $i++) {
    $location1 = $allNoiseData[$i];
    $clusteredLocations['index_' . $i] = array();
    if (!array_search($location1, $addedLocationsArray)) {
        $clusteredLocations['index_' . $i] = array($location1);
        $addedLocationsArray['index_' . $location1['id']] = $location1;
    }
    for ($j = 0; $j < count($allNoiseData); $j++) {
        $location2 = $allNoiseData[$j];
        $distance = $baseService->getDisplacement(array('lat' => $location1['latitude'], 'long' => $location1['longitude']), array('lat' => $location2['latitude'], 'long' => $location2['longitude']));
        if ($distance <= $clusterRange && $location1 != $location2) {
            if (!array_search($location2, $addedLocationsArray)) {
                $clusteredLocations['index_' . $i] = array_merge($clusteredLocations['index_' . $i], array($location2));
                $addedLocationsArray['index_' . $location2['id']] = $location2;
            }
        }
    }
}

foreach ($clusteredLocations as $cluster) {
    $clusterNoiseValues = array();
    $locationArray = array();
    foreach ($cluster as $noiseDataRecord) {
        $clusterNoiseValues[] = $noiseDataRecord['noise_level'];
        $locationArray[$noiseDataRecord['location_id']] = $noiseDataRecord['location_id'];
    }
    $mean = 0;
    $mod = 0;
    $median = 0;
    $sd = 0; 
    sort($clusterNoiseValues);
    if (count($clusterNoiseValues)) {
        $mean = array_sum($clusterNoiseValues) / count($clusterNoiseValues);

        $ar = array_replace($clusterNoiseValues, array_fill_keys(array_keys($clusterNoiseValues, null), ''));
        $count = array_count_values($ar);
        $mod = array_search(max($count), $count);

        $middle = round(count($clusterNoiseValues) / 2);
        $median = $clusterNoiseValues[$middle - 1];

        $variance = 0.0;
        foreach ($clusterNoiseValues as $i) {
            $variance += pow($i - $mean, 2);
        }
        $size = count($clusterNoiseValues) - 1;
        if ($size > 0) {
            $sd = (float) sqrt($variance) / sqrt($size);
        } else {
            $sd = 0;
        }
    }

    foreach ($locationArray as $locationId) {
        $dateTime = time();
        $insertStatisticsQuery = "INSERT INTO `cdme_location_point_statistics` (`location_id`,`mod`,`median`,`mean`,`sd`,`date_time`) VALUES ('" . $locationId . "','" . $mod . "','" . $median . "','" . $mean . "','" . $sd . "','" . $dateTime . "');";
        $dbHandler->executeQuery($insertStatisticsQuery);
    }
}



