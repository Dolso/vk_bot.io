<?php

class PDOdb
{
    protected static $_instance;

    public $host;
    public $dbname;
    public $login;
    public $password;

    private function __construct($host, $dbname, $login, $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->login = $login;
        $this->password = $password;
    }

    public static function getInstance($host, $dbname, $login, $password)
    {
        if (self::$_instance === null) {
            self::$_instance = new self($host, $dbname, $login, $password);
        }
        return self::$_instance;
    }

    private function __clone()
    {

    }

    private function __wakeup()
    {

    }

    function insert($id, $meadal, $nomer, $pravilotv, $name, $popitki, $active)
    {
        try {
            
            $DBH = new PDO("mysql:host=".$this->host.";dbname=".$this->dbname.";charset=UTF8", $this->login, $this->password);
            $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $data = array($id,$meadal,$nomer,$pravilotv,$name,$popitki,$active);
            $STH = $DBH -> prepare("INSERT INTO tik (id, rank, nomer, pravilotv, name, popitki, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $STH -> execute($data);
        }
        catch(PDOException $e) {
            echo "PROBLEVA S INSERT";
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    }
    //функция для запроса UPDATE, если $d true то нужно прибавить к параметру 1
    function update($parametr, $znach, $chat_id, $d)
    {
        try {
        	
            $DBH = new PDO("mysql:host=".$this->host.";dbname=".$this->dbname.";charset=UTF8", $this->login, $this->password);
            $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            if ($d == true) {
                $stmt = $DBH-> query("SELECT ".$parametr." FROM tik WHERE id =".$chat_id);
                $stmt-> setFetchMode(PDO::FETCH_ASSOC);
                $row = $stmt -> fetch();
                $row[$parametr] += 1;
                $data = array($row[$parametr],$chat_id);
                $STH = $DBH -> prepare("UPDATE tik SET ".$parametr." = ? WHERE id = ?");
                $STH -> execute($data);
            }
            else {
                $data = array($znach,$chat_id);
                $STH = $DBH -> prepare("UPDATE tik SET ".$parametr." = ? WHERE id = ?");
                $STH -> execute($data);
            }
        }
        catch(PDOException $e) {
            echo "PROBLEMA S UPDATE";
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    }
    //функция для запроса SELECT
    function select($parametr, $chat_id)
    {
        try {

            $DBH = new PDO("mysql:host=".$this->host.";dbname=".$this->dbname.";charset=UTF8", $this->login, $this->password);
            $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $stmt = $DBH->query("SELECT ".$parametr." FROM tik WHERE id =".$chat_id);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $row = $stmt->fetch();
            return $row;
        }
        catch(PDOException $e) {
            echo "PROBLEMA S SELECT";
            file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    }
}

?>