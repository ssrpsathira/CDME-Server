<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BaseCdmeService
 *
 * @author ssrp
 */
require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

class BaseCdmeService {

    protected $databaseHandler;
    const EARTH_RADIUS = 6371000;

    /**
     * 
     * @return DatabaseHandler
     */
    public function getDatabaseHandler() {
        if (!$this->databaseHandler) {
            $this->databaseHandler = new DatabaseHandler();
        }
        return $this->databaseHandler;
    }

    public function getDisplacement($location1, $location2) {
        // convert from degrees to radians
        $latFrom = deg2rad($location1['lat']);
        $lonFrom = deg2rad($location1['long']);
        $latTo = deg2rad($location2['lat']);
        $lonTo = deg2rad($location2['long']);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
                pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * self::EARTH_RADIUS;
    }

}
