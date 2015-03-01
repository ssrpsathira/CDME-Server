<?php

/**
 * Description of DatabaseHandlerTest
 *
 * @author ssrp
 */
class DatabaseHandlerTest extends PHPUnit_Framework_TestCase {
    
    /** @var DatabaseHandler */
    private $dbHandler = null;
    /** @var TestUtility */
    private $testUtility = null;
    
    public static function setUpBeforeClass() {
        require_once __DIR__ . '/../Engine/DatabaseHandler.php';
        require_once __DIR__ . '/TestUtility.php';
    }
    
    protected function setUp() {
        $this->dbHandler = new DatabaseHandler(null, null, null, DatabaseConfig::TEST_DB_NAME);
        $this->testUtility = TestUtility::getInstance();
    }
    
    /**
     * @covers DatabaseHandler::getDatabaseConnection
     */
    public function testGetDatabaseConnection() {
        $this->assertTrue($this->dbHandler->getDatabaseConnection() instanceof PDO);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $this->dbHandler->getDatabaseConnection()->getAttribute(PDO::ATTR_ERRMODE));
    }
    
    public function testExecuteQuery() {
        $this->assertTrue(is_array($this->dbHandler->executeQuery('select 1')));
    }
    
    public function testExecuteQueryWithParams_InsertLocation() {
        $this->testUtility->setLocations(5);
        $this->assertEquals(5, $this->testUtility->fetchLocationCount());
        $params = array("79.859795599413","6.890167995075");
        $query = 'SELECT * FROM cdme_location WHERE longitude = ? AND latitude = ?';
        $result = $this->dbHandler->executeQueryWithParams($query, $params);
        $this->assertTrue(is_array($result) && empty($result));
        $result1 = $this->dbHandler->executeQueryWithParams($query, array("79.860022983748","6.8907942655994"));
        $this->assertTrue(is_array($result1) && count($result1) === 1 && is_array($result1[0]));
        $params1 = array("9","79.859795599413","6.890167995075","9","5");
        $query1 = 'INSERT INTO cdme_location VALUES(?,?,?,?,?)';
        $this->assertEquals(1, $this->dbHandler->executeQueryWithParams($query1, $params1, true));
        $namedQuery = 'INSERT INTO cdme_location VALUES(:id, :longitude, :latitude, :admin_1_region_id, :admin_2_region_id)';
        $namedParams = array(':id' => "10", ':longitude' => "79.859404669297", ':latitude' => "6.8899954465659", ':admin_1_region_id' => "9", ':admin_2_region_id' => "5");
        $this->assertEquals(1, $this->dbHandler->executeQueryWithParams($namedQuery, $namedParams, true));
        $this->assertEquals(7, $this->testUtility->fetchLocationCount());
    }
}
