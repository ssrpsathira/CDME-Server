<?php

require_once __DIR__ . '/../ServiceProvider/BaseCdmeService.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BaseCdmeServiceTest
 *
 * @author ssrp
 */
class BaseCdmeServiceTest extends PHPUnit_Framework_TestCase {

    private $baseCdmeService = null;

    protected function setUp() {
        $this->baseCdmeService = new BaseCdmeService();
    }

    public function testSetAndGetDatabaseHandler() {
        $mockDbHandler = $this->getMock('DatabaseHandler');
        $this->baseCdmeService->setDatabaseHandler($mockDbHandler);
        $this->assertEquals($mockDbHandler, $this->baseCdmeService->getDatabaseHandler());
    }

    public function testGetDisplacement() {
        $location1 = array('lat' => 6.798578, 'long' => 79.898994);
        $location2 = array('lat' => 6.798541, 'long' => 79.899079);
        $distance = $this->baseCdmeService->getDisplacement($location1, $location2);
        $this->assertTrue($distance > 10 && $distance < 11);
    }

}
