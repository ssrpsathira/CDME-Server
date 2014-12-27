<?php

echo "Hostname: ";
$handle = fopen("php://stdin", "r");
$servername = trim(fgets($handle));
//$servername = gethostbyname(gethostname());

echo "MySQL Username: ";
$handle = fopen("php://stdin", "r");
$username = trim(fgets($handle));

echo 'MySQL Password: ';
$handle = fopen("php://stdin", "r");
$password = trim(fgets($handle));

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "creating database...\n";
$sql = "CREATE DATABASE `db_cdme`";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

echo "creating tables...";
$sql = file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_tables.sql');
if ($conn->query($sql) === TRUE) {
    echo "Tables created successfully\n";
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

echo "inserting static table values...\n";
$sql = file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_table_values.sql');
if ($conn->query($sql) === TRUE) {
    echo "Tables values inserted successfully\n";
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

$conn->close();
?>