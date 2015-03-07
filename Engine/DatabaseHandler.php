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

    public function __construct($host = null, $username = null, $password = null, $dbName = null) {
        if (!is_null($host)) {
            $this->host = $host;
        }
        if (!is_null($username)) {
            $this->username = $username;
        }
        if (!is_null($password)) {
            $this->password = $password;
        }
        if (!is_null($dbName)) {
            $this->dbName = $dbName;
        }
    }

    /**
     * 
     * @return PDO
     */
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

    public function executeQueryWithParams($query, $params = array(), $returnCount = false) {
        try {
            $sth = $this->getDatabaseConnection()->prepare($query);
            if ($sth->execute($params)) {
                return $returnCount ? $sth->rowCount() : $sth->fetchAll();
            }
            return null;
        } catch (PDOException $ex) {
            
        }
    }

    public function executeQuery($sqlQuery) {
        try {
            if ($sth = $this->getDatabaseConnection()->query($sqlQuery)) {
                return $sth->fetchAll();
            } else {
                var_dump($this->getDatabaseConnection()->errorCode());
                var_dump($this->getDatabaseConnection()->errorInfo());
            }
            return null;
        } catch (PDOException $e) {
            
        }
    }

}
