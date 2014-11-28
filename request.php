<?php
require_once dirname(__FILE__).'/ServiceProvider/ServiceFactory.php';

$fp = fopen('php://input', 'r');
$rawData = json_decode(stream_get_contents($fp), true);
$serviceFactory = new ServiceFactory($rawData);

echo 'success';
?>