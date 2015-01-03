<?php

require_once dirname(__FILE__) . '/NoiseDataService.php';
require_once dirname(__FILE__) . '/../Config/DatabaseConfig.php';
require_once dirname(__FILE__) . '/ConfigService.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ServiceFactory
 *
 * @author ssrp
 */
class ServiceFactory {

    const SERVICE_MODE_UPLOAD = 'upload';
    const SERVICE_MODE_DOWNLOAD = 'download';
    const SERVICE_PREFIX_NOISE = 'noise';

    protected $servicePrefix;
    protected $dataService;
    protected $configurationService;

    protected function getDataService() {
        if (!$this->dataService) {
            switch ($this->getServicePrefix()):
                case self::SERVICE_PREFIX_NOISE:
                    $this->dataService = new NoiseDataService();
                    break;
            endswitch;
        }
        return $this->dataService;
    }

    protected function getConfigurationService() {
        if (!$this->configurationService) {
            $this->configurationService = new ConfigService();
        }
        return $this->configurationService;
    }

    public function __construct() {

    }

    public function processData($data) {
        $metaData = $data['metadata'];
        $this->setServicePrefix($metaData['service']);
        if ($metaData['mode'] == self::SERVICE_MODE_UPLOAD) {
            $result = $this->getDataService()->initializeDataUploadService($data);
        } else {
            $result = $this->getDataService()->initializeDataDownloadService($data);
        }
        return $result;
    }

    function getServicePrefix() {
        return $this->servicePrefix;
    }

    function setServicePrefix($servicePrefix) {
        $this->servicePrefix = $servicePrefix;
    }

}
