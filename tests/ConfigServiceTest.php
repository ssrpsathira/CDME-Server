<?php

require_once __DIR__ . '/../ServiceProvider/ConfigService.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConfigServiceTest
 *
 * @author ssrp
 */
class ConfigServiceTest extends PHPUnit_Framework_TestCase {

    private $configService = null;

    protected function setUp() {
        $this->configService = new ConfigService();
    }

    public function testGetDatabaseHandler() {
        $result = $this->configService->getDatabaseHandler();
        $this->assertTrue($result instanceof DatabaseHandler);
    }

}
