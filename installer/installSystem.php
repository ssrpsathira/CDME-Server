<?php

set_time_limit(0);

$dbName = 'db_cdme';

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
$sql = "CREATE DATABASE $dbName;";
if ($conn->query($sql) === TRUE) {
    echo "$dbName Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

$conn = new mysqli($servername, $username, $password, $dbName);

/* check if server is alive */
if ($conn->ping()) {
    printf("Our connection is ok!\n");
} else {
    printf("Error: %s\n", $conn->error);
}

echo "creating tables...\n";
$sql = file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_tables.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        $conn->use_result();
    } while ($conn->more_results() && $conn->next_result());
    echo "Tables created successfully\n";
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

/* check if server is alive */
if ($conn->ping()) {
    printf("Our connection is ok!\n");
} else {
    printf("Error: %s\n", $conn->error);
}

echo "inserting static table values...\n";
$sql = file_get_contents(dirname(__FILE__) . '/../DbScripts/noise_service_table_values.sql');
if (mysqli_multi_query($conn, $sql)) {
    do {
        $conn->use_result();
    } while ($conn->more_results() && $conn->next_result());
    echo "Tables values inserted successfully\n";
} else {
    echo "Error inserting tables values: $conn->errno :" . $conn->error . "\n";
}

$conn->close();
?>