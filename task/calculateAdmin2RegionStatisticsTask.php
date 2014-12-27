<?php

require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

$dbHandler = new DatabaseHandler();
$resultQuery = "SELECT `cdme_admin_2_region`.id, `cdme_noise_data`.noise_level FROM `cdme_admin_2_region` LEFT JOIN `cdme_location` ON `cdme_location`.admin_1_region_id = `cdme_admin_2_region`.id LEFT JOIN `cdme_noise_data` ON `cdme_noise_data`.location_id = `cdme_location`.id";
$result = $dbHandler->executeQuery($resultQuery);
foreach ($result as $set) {
    if (empty($regionNoiseArray[$set['id']])) {
        $regionNoiseArray[$set['id']] = array();
    }
    $regionNoiseArray[$set['id']] = array_merge($regionNoiseArray[$set['id']], array($set['noise_level']));
}

foreach ($regionNoiseArray as $regionId => $regionNoiseValues) {
    sort($regionNoiseValues);
    $mean = array_sum($regionNoiseValues) / count($regionNoiseValues);

    $ar = array_replace($regionNoiseValues, array_fill_keys(array_keys($regionNoiseValues, null), ''));
    $count = array_count_values($ar);
    $mod = array_search(max($count), $count);

    $middle = round(count($regionNoiseValues) / 2);
    $median = $regionNoiseValues[$middle - 1];

    $variance = 0.0;
    foreach ($regionNoiseValues as $i) {
        $variance += pow($i - $mean, 2);
    }
    $size = count($regionNoiseValues) - 1;
    if ($size > 0) {
        $sd = (float) sqrt($variance) / sqrt($size);
    } else {
        $sd = 0;
    }

    $dateTime = time();

    $insertStatisticsQuery = "INSERT INTO `cdme_admin_2_region_statistics` (`region_id`,`mod`,`median`,`mean`,`sd`,`date_time`) VALUES ('" . $regionId . "','" . $mod . "','" . $median . "','" . $mean . "','" . $sd . "','" . $dateTime . "');";
    $dbHandler->executeQuery($insertStatisticsQuery);
}

