<?php

require_once __DIR__ . '/../Config/DatabaseConfig.php';

/**
 * Description of TestUtility
 *
 * @author ssrp
 */
class TestUtility {

    /**
     * database connection
     * @var PDO 
     */
    private $dbConnection = null;
    
    private static $instance = null;

    public function __construct(array $config = array()) {
        if (isset($config['dsn'])) {
            $dsn = $config['dsn'];
        } else {
            $host = isset($config['host']) ? $config['host'] : DatabaseConfig::HOST;
            $port = isset($config['port']) ? $config['port'] : DatabaseConfig::PORT;
            $dbName = isset($config['dbName']) ? $config['dbName'] : DatabaseConfig::TEST_DB_NAME;
            $dsn = "mysql:$host=localhost;port=$port;dbname=$dbName";
        }
        $username = isset($config['username']) ? $config['username'] : DatabaseConfig::USER_NAME;
        $password = isset($config['password']) ? $config['password'] : DatabaseConfig::PASSWORD;
        $this->dbConnection = new PDO($dsn, $username, $password);
        $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TestUtility();
        }
        return self::$instance;
    }

    /**
     * get database connection
     * @return PDO
     */
    public function getDbConnection() {
        return $this->dbConnection;
    }
    
    /**
     * set database connection
     * @param PDO $dbConnection
     */
    public function setDbConnection(PDO $dbConnection) {
        $this->dbConnection = $dbConnection;
    }
    
    protected function executeQuery($query, $returnResults = false) {
        $dbh = $this->getDbConnection();
        if ($returnResults) {
            $sth = $dbh->query($query);
            return $sth->fetchAll();
        }
        return $dbh->exec($query);
    }
    
    protected function fetchScalarValue($query) {
        return $this->getDbConnection()->query($query)->fetchColumn();
    }
    
    protected function executeTruncateQuery($query) {
        $this->disableFKConstraints();
        $this->executeQuery($query);
        $this->enableFKConstraints();
    }
    
    protected function disableFKConstraints() {
        $query = 'set foreign_key_checks = 0';
        $this->executeQuery($query);
    }
    
    protected function enableFKConstraints() {
        $query = 'set foreign_key_checks = 1';
        $this->executeQuery($query);
    }
    
    /**
     * Inserts given rows to given table and returns number of rows inserted.
     * @param string $tableName
     * @param array $columns
     * @param array $rows
     * @return int
     */
    protected function insertTableValues($tableName, $columns, $rows) {
        $insertCount = 0;
        $fields = '`' . implode('`, `', $columns). '`';
        $placeholder = rtrim(str_repeat('?,', count($columns)), ',');
        $query = "INSERT INTO `$tableName`($fields) VALUES($placeholder)";
        $sth = $this->getDbConnection()->prepare($query);
        foreach ($rows as $row) {
            if ($sth->execute($row)) {
                $insertCount++;
            }
        }
        return $insertCount;
    }
    
    private static function getFixturePath() {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR;
    }


    private static function getFilePath ($fileName) {
        if (is_file($fileName)) {
            $filePath = $fileName;
        } elseif (is_file(self::getFixturePath() . $fileName)) {
            $filePath = self::getFixturePath() . $fileName;
        } else {
            throw new Exception(sprintf("File not found. File name: '%s'.\n", $fileName));
        }
        return $filePath;
    }
    
    public static function readFixtureData($fileName, $numberOfRows = 0, $asAssocArray = false) {
        $filePath = self::getFilePath($fileName);
        if ($numberOfRows === 0) {
            $rows = array_map('str_getcsv', file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        } else {
            $rowCount = 0;
            if (($fp = fopen($filePath, "r")) !== FALSE) {
                $rows = array(fgetcsv($fp)); // header row
                do {
                    $rows[] = fgetcsv($fp);
                    $rowCount++;
                } while ($rowCount < $numberOfRows);
                fclose($fp);
            }
        }
        if ($asAssocArray) {
            $headerRow = array_shift($rows);
            return array_map('array_combine', array_fill(0, count($rows), $headerRow), $rows);
        }
        return $rows;
    }

    public function truncateLocations() {
        $query = 'truncate cdme_location';
        $this->executeTruncateQuery($query);
    }
    
    public function truncateUsers() {
        $query = 'truncate cdme_user';
        $this->executeTruncateQuery($query);
    }
    
    public function truncateNoiseData() {
        $query = 'truncate cdme_noise_data';
        $this->executeTruncateQuery($query);
    }
    
    public function fetchLocationCount() {
        $query = 'select count(*) from cdme_location';
        return $this->fetchScalarValue($query);
    }
    
    /**
     * truncates location table and inserts given number of location data from locations fixture
     * @param type $numberOfLocations
     * @return number of location records that were inserted
     */
    public function setLocations($numberOfLocations = 0) {
        $this->truncateLocations();
        $locations = self::readFixtureData('locations.csv', $numberOfLocations);
        $columns = array_shift($locations);
        return $this->insertTableValues('cdme_location', $columns, $locations);
    }
    
    public function setUsers($numberOfUsers = 0) {
        $this->truncateUsers();
        $users = self::readFixtureData('users.csv', $numberOfUsers);
        $columns = array_shift($users);
        return $this->insertTableValues('cdme_user', $columns, $users);
    }
    
    public function setNoiseData($numberOfRecords = 0) {
        $this->truncateNoiseData();
        $data = self::readFixtureData('noise_data.csv', $numberOfRecords);
        $columns = array_shift($data);
        return $this->insertTableValues('cdme_noise_data', $columns, $data);
    }
    
    public function addUsers(array $users) {
        
    }
    
    public function addNoiseData(array $noiseData) {
        
    }

}
