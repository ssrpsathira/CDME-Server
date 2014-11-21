<?php

require_once dirname(__FILE__).'/NoiseDataService.php';
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

    public function __construct($data) {
        $metaData = $data['metadata'];
        $this->setServicePrefix($metaData['service']);
        if ($metaData['mode'] == self::SERVICE_MODE_UPLOAD) {
            $this->getDataService()->initializeDataUploadService($data['rawdata']);
        } else {
            $this->getDataService()->initializeDataDownloadService($data['rawdata']);
        }
    }

    function getServicePrefix() {
        return $this->servicePrefix;
    }

    function setServicePrefix($servicePrefix) {
        $this->servicePrefix = $servicePrefix;
    }

}
