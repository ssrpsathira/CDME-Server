<?php

/**
 * test class for TestUtility
 *
 * @author ssrp
 */
class TestUtilityTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        require_once __DIR__ . '/TestUtility.php';
    }

    public function testReadFixtureData_Default() {
        $result = TestUtility::readFixtureData('test.csv');
        $this->assertTrue(is_array($result));
        $this->assertEquals(9, count($result)); // header row + 8 data rows
        $expectedHeaderRow = array('AAA', 'BBB', 'CCC', 'DDD', 'EEE');
        $actualHeaderRow = array_shift($result);
        $this->assertEquals($expectedHeaderRow, $actualHeaderRow);
        $this->assertEquals(8, count($result)); // 8 data rows
        $this->assertEquals(array(2, 3, 4, 5, 6), $result[3]); // 4th data row
    }

    public function testReadFixtureData_firstTwoRows() {
        $result = TestUtility::readFixtureData('test.csv', 3);
        $this->assertTrue(is_array($result));
        $this->assertEquals(4, count($result)); // header row + 3 data rows
        $expectedHeaderRow = array('AAA', 'BBB', 'CCC', 'DDD', 'EEE');
        $actualHeaderRow = array_shift($result);
        $this->assertEquals($expectedHeaderRow, $actualHeaderRow);
        $this->assertEquals(3, count($result)); // 3 data rows
        $this->assertEquals(array(2, 4, 6, 8, 10), $result[1]); // 2nd data row
    }

    public function testReadFixtureData_firstTwoRows_asAssocArray() {
        $result = TestUtility::readFixtureData('test.csv', 2, true);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $expected = array(
            array(
                'AAA' => 1,
                'BBB' => 2,
                'CCC' => 3,
                'DDD' => 4,
                'EEE' => 5,
            ),
            array(
                'AAA' => 2,
                'BBB' => 4,
                'CCC' => 6,
                'DDD' => 8,
                'EEE' => 10,
        ));
        $this->assertEquals($expected, $result);
    }

}
