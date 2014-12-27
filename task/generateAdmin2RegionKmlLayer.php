<?php

require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

$dbHandler = new DatabaseHandler();

$getStatisticalDataQuery = "SELECT DISTINCT(`cdme_admin_2_region_statistics`.region_id), `cdme_admin_2_region`.name, `cdme_admin_2_region_statistics`.mod, `cdme_admin_2_region_statistics`.median, `cdme_admin_2_region_statistics`.mean, `cdme_admin_2_region_statistics`.sd FROM `cdme_admin_2_region_statistics` LEFT JOIN `cdme_admin_2_region` ON `cdme_admin_2_region`.id = `cdme_admin_2_region_statistics`.region_id ORDER BY `cdme_admin_2_region_statistics`.date_time DESC";
$result = $dbHandler->executeQuery($getStatisticalDataQuery);
var_dump($result);die;

