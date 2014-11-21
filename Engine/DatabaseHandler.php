<?php

require_once 'Config/DatabaseConfig.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DatabaseHandler
 *
 * @author ssrp
 */
class DatabaseHandler {

    protected $host = DatabaseConfig::HOST;
    protected $username = DatabaseConfig::USER_NAME;
    protected $password = DatabaseConfig::PASSWORD;
    protected $dbName = DatabaseConfig::DB_NAME;
    protected $dbConnection;

    public function getDatabaseConnection() {
        if (!$this->dbConnection) {
            try {
                $this->dbConnection = new PDO("mysql:host=$this->host;dbname=$this->dbName", $this->username, $this->password);
                // set the PDO error mode to exception
                $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }
        return $this->dbConnection;
    }

    public function executeQuery($sqlQuery) {
        try {
            return $this->getDatabaseConnection()->exec($sqlQuery);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

}
