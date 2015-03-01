<?php

require_once __DIR__ . '/../ServiceProvider/NoiseDataService.php';
/**
 * Description of NoiseDataServiceTest
 *
 * @author ssrp
 */
class NoiseDataServiceTest extends PHPUnit_Framework_TestCase {

    private $noiseDataService = null;

    public static function setUpBeforeClass() {        
        //require_once __DIR__ . '/TestUtility.php';
    }

    protected function setUp() {
        $this->noiseDataService = new NoiseDataService();
    }

    public function testIsExistingLocation_NonExistingLocation() {
        $longitude = "79.859404669293";
        $latitude = "6.8899954465655";
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_location` WHERE `longitude`=? AND `latitude`=? LIMIT 1;', array($longitude, $latitude))
                ->will($this->returnValue(array()));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals(null, $this->noiseDataService->isExistingLocation($longitude, $latitude));
    }

    public function testIsExistingLocation_ExistingLocation() {
        $longitude = "79.859404669293";
        $latitude = "6.8899954465655";
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_location` WHERE `longitude`=? AND `latitude`=? LIMIT 1;', array($longitude, $latitude))
                ->will($this->returnValue(array(array('id' => 1))));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals(1, $this->noiseDataService->isExistingLocation($longitude, $latitude));
    }

    public function testIsExistingUser_NonExistingUser() {
        $imei = "358263054833546";
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_user` WHERE `imei` =? LIMIT 1;', array($imei))
                ->will($this->returnValue(array()));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals(null, $this->noiseDataService->isExistingUser($imei));
    }

    public function testIsExistingUser_ExistingUser() {
        $imei = "358263054833546";
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_user` WHERE `imei` =? LIMIT 1;', array($imei))
                ->will($this->returnValue(array(array('id' => 1))));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals(1, $this->noiseDataService->isExistingUser($imei));
    }

    // there should be tests for getReverseGeoCodingLocationDetails


    public function adminRegion1DataProvider() {
        return array(
            array('Central Province', 1),
            array('Northern Province', 4),
            array('Western Province', 9)
        );
    }

    /**
     * @dataProvider adminRegion1DataProvider
     * @param string $admin1RegionName
     * @param int $id
     */
    public function testGetAdmin1RegionIdByName($admin1RegionName, $id) {
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_admin_1_region` WHERE `name`=?', array(trim($admin1RegionName)))
                ->will($this->returnValue(array(array('id' => $id))));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals($id, $this->noiseDataService->getAdmin1RegionIdByName($admin1RegionName));
    }

    public function testObtainAdmin1RegionIdByLatLong() {
        $longitude = "79.859404669293";
        $latitude = "6.8899954465655";
        $mockNoiseDataService = $this->getMock('NoiseDataService', array('getReverseGeoCodingLocationDetails', 'getAdmin1RegionIdByName'));
        $returnVal = array(
            'admin_1' => 'Central Province'
                // some other data
        );
        $mockNoiseDataService->expects($this->once())
                ->method('getReverseGeoCodingLocationDetails')
                ->with($latitude, $longitude)
                ->will($this->returnValue($returnVal));
        $mockNoiseDataService->expects($this->once())
                ->method('getAdmin1RegionIdByName')
                ->with('Central Province')
                ->will($this->returnValue(1));
        $this->assertEquals(1, $mockNoiseDataService->obtainAdmin1RegionIdByLatLong($latitude, $longitude));
    }

    public function adminRegion2DataProvider() {
        return array(
            array('Ampara', 1),
            array('Colombo', 5),
            array('Matara', 17)
        );
    }

    /**
     * @dataProvider adminRegion2DataProvider
     * @param string $admin2RegionName
     * @param type $id
     */
    public function testGetAdmin2RegionIdByName($admin2RegionName, $id) {
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('SELECT `id` FROM `cdme_admin_2_region` WHERE `name`=?', array(trim($admin2RegionName)))
                ->will($this->returnValue(array(array('id' => $id))));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals($id, $this->noiseDataService->getAdmin2RegionIdByName($admin2RegionName));
    }

    public function testObtainAdmin2RegionIdByLatLong() {
        $longitude = "79.859404669293";
        $latitude = "6.8899954465655";
        $mockNoiseDataService = $this->getMock('NoiseDataService', array('getReverseGeoCodingLocationDetails', 'getAdmin2RegionIdByName'));
        $returnVal = array(
            'admin_2' => 'Ampara'
                // some other data
        );
        $mockNoiseDataService->expects($this->once())
                ->method('getReverseGeoCodingLocationDetails')
                ->with($latitude, $longitude)
                ->will($this->returnValue($returnVal));
        $mockNoiseDataService->expects($this->once())
                ->method('getAdmin2RegionIdByName')
                ->with('Ampara')
                ->will($this->returnValue(1));
        $this->assertEquals(1, $mockNoiseDataService->obtainAdmin2RegionIdByLatLong($latitude, $longitude));
    }

    public function testSaveCurrentLocation() {
        $longitude = "79.859404669293";
        $latitude = "6.8899954465655";
        $admin2RegionId = 5;
        $admin1RegionId = 9;
        $mockNoiseDataService = $this->getMock('NoiseDataService', array('obtainAdmin2RegionIdByLatLong', 'obtainAdmin1RegionIdByLatLong'));
        $mockNoiseDataService->expects($this->once())
                ->method('obtainAdmin2RegionIdByLatLong')
                ->with($latitude, $longitude)
                ->will($this->returnValue($admin2RegionId));
        $mockNoiseDataService->expects($this->once())
                ->method('obtainAdmin1RegionIdByLatLong')
                ->with($latitude, $longitude)
                ->will($this->returnValue($admin1RegionId));
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('INSERT INTO `cdme_location` (`longitude`, `latitude`, `admin_2_region_id`,`admin_1_region_id`) VALUES (?, ?, ?, ?);', array($longitude, $latitude, $admin2RegionId, $admin1RegionId), true)
                ->will($this->returnValue(1));
        $mockNoiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertTrue($mockNoiseDataService->saveCurrentLocation($longitude, $latitude));
    }
    
    public function testSaveCurrentUser() {
        $imei = "358263054833546";
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with('INSERT INTO `cdme_user` (`imei`) VALUES (?);', array($imei), true)
                ->will($this->returnValue(1));
        $this->noiseDataService->setDatabaseHandler($mockDbHandler);
        $this->assertTrue($this->noiseDataService->saveCurrentUser($imei));
    }
    
    public function testCreateDataUploadQuery_Default() {
        $rawData = array(
            'latitude' => 6.8899954465655,
            'longitude' => 79.859404669293,
            'noise_level' => 31.12828346606,
            'date_time' => 1423887702
        );
        $metaData = array('imei' => "358263054833546", 'feature' => 'noiseData');
        $data = array('rawdata' => $rawData, 'metadata' => $metaData);
        $mockNoiseDataService = $this->getMock('TestNoiseDataService', array('isExistingLocation', 'isExistingUser'));
        $mockNoiseDataService->expects($this->once())
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(707));
        $mockNoiseDataService->expects($this->once())
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(5));
        $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (?,?,?,?);";
        $params = array(5, 707, $rawData['noise_level'], $rawData['date_time']);
        $this->assertEquals(array($dataInsertQuery, $params), $mockNoiseDataService->createDataUploadQuery($data));
    }
    
    public function testCreateDataUploadQuery_NonExistingLocation() {
        $rawData = array(
            'latitude' => 6.8899954465655,
            'longitude' => 79.859404669293,
            'noise_level' => 31.12828346606,
            'date_time' => 1423887702
        );
        $metaData = array('imei' => "358263054833546", 'feature' => 'noiseData');
        $data = array('rawdata' => $rawData, 'metadata' => $metaData);
        $mockNoiseDataService = $this->getMock('TestNoiseDataService', array('isExistingLocation', 'isExistingUser', 'saveCurrentLocation'));
        $mockNoiseDataService->expects($this->at(0))
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(null));
        $mockNoiseDataService->expects($this->at(1))
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(5));
        $mockNoiseDataService->expects($this->at(2))
                ->method('saveCurrentLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(true));
        $mockNoiseDataService->expects($this->at(3))
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(707));
        $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (?,?,?,?);";
        $params = array(5, 707, $rawData['noise_level'], $rawData['date_time']);
        $this->assertEquals(array($dataInsertQuery, $params), $mockNoiseDataService->createDataUploadQuery($data));
    }
    
    public function testCreateDataUploadQuery_NonExistingUser() {
        $rawData = array(
            'latitude' => 6.8899954465655,
            'longitude' => 79.859404669293,
            'noise_level' => 31.12828346606,
            'date_time' => 1423887702
        );
        $metaData = array('imei' => "358263054833546", 'feature' => 'noiseData');
        $data = array('rawdata' => $rawData, 'metadata' => $metaData);
        $mockNoiseDataService = $this->getMock('TestNoiseDataService', array('isExistingLocation', 'isExistingUser', 'saveCurrentUser'));
        $mockNoiseDataService->expects($this->at(0))
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(707));
        $mockNoiseDataService->expects($this->at(1))
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(null));
        $mockNoiseDataService->expects($this->at(2))
                ->method('saveCurrentUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(true));
        $mockNoiseDataService->expects($this->at(3))
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(5));
        $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (?,?,?,?);";
        $params = array(5, 707, $rawData['noise_level'], $rawData['date_time']);
        $this->assertEquals(array($dataInsertQuery, $params), $mockNoiseDataService->createDataUploadQuery($data));
    }
    
    public function testCreateDataUploadQuery_NonExistingUserAndLocation() {
        $rawData = array(
            'latitude' => 6.8899954465655,
            'longitude' => 79.859404669293,
            'noise_level' => 31.12828346606,
            'date_time' => 1423887702
        );
        $metaData = array('imei' => "358263054833546", 'feature' => 'noiseData');
        $data = array('rawdata' => $rawData, 'metadata' => $metaData);
        $mockNoiseDataService = $this->getMock('TestNoiseDataService', array('isExistingLocation', 'isExistingUser', 'saveCurrentLocation', 'saveCurrentUser'));
        $mockNoiseDataService->expects($this->at(0))
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(null));
        $mockNoiseDataService->expects($this->at(1))
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(null));
        $mockNoiseDataService->expects($this->at(2))
                ->method('saveCurrentLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(true));
        $mockNoiseDataService->expects($this->at(3))
                ->method('isExistingLocation')
                ->with($rawData['longitude'], $rawData['latitude'])
                ->will($this->returnValue(707));
        $mockNoiseDataService->expects($this->at(4))
                ->method('saveCurrentUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(true));
        $mockNoiseDataService->expects($this->at(5))
                ->method('isExistingUser')
                ->with($metaData['imei'])
                ->will($this->returnValue(5));
        $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (?,?,?,?);";
        $params = array(5, 707, $rawData['noise_level'], $rawData['date_time']);
        $this->assertEquals(array($dataInsertQuery, $params), $mockNoiseDataService->createDataUploadQuery($data));
    }
    
    public function testInitializeDataUploadService() {
        $rawData = array(
            'latitude' => 6.8899954465655,
            'longitude' => 79.859404669293,
            'noise_level' => 31.12828346606,
            'date_time' => 1423887702
        );
        $metaData = array('imei' => "358263054833546", 'feature' => 'noiseData');
        $data = array('rawdata' => $rawData, 'metadata' => $metaData);
        
        $dataInsertQuery = "INSERT INTO `cdme_noise_data` (`user_id`, `location_id`,`noise_level`,`date_time`) VALUES (?,?,?,?);";
        $params = array(5, 707, $rawData['noise_level'], $rawData['date_time']);
        $mockNoiseDataService = $this->getMock('TestNoiseDataService', array('createDataUploadQuery'));
        $mockNoiseDataService->expects($this->once())
                ->method('createDataUploadQuery')
                ->with($data)
                ->will($this->returnValue(array($dataInsertQuery, $params)));
        
        $mockDbHandler = $this->getMock('DatabaseHandler', array('executeQueryWithParams'));
        $mockDbHandler->expects($this->once())
                ->method('executeQueryWithParams')
                ->with($dataInsertQuery, $params)
                ->will($this->returnValue(array()));
        $mockNoiseDataService->setDatabaseHandler($mockDbHandler);
        
        $expected = json_encode(array());
        $this->assertEquals($expected, $mockNoiseDataService->initializeDataUploadService($data));
    }

}

class TestNoiseDataService extends NoiseDataService {
    public function createDataUploadQuery($data) {
        return parent::createDataUploadQuery($data);
    }
}
