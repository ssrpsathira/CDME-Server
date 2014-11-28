<?php

require_once dirname(__FILE__) . '/../Config/DatabaseConfig.php';
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
            return $this->getDatabaseConnection()->query($sqlQuery)->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function createDatabase() {
        try {
            $dbh = new PDO("mysql:host=$this->host", $this->username, $this->password);

            $dbh->exec("CREATE DATABASE `$this->dbName`;")
                    or die(print_r($dbh->errorInfo(), true));
        } catch (PDOException $e) {
            die("DB ERROR: " . $e->getMessage());
        }
    }

}
