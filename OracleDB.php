<?php

class OracleDB
{
    private $pdo;
    private $host;
    private $port;
    private $serviceName;
    private $username;
    private $password;
    private $timeout;

    public function __construct($host, $port, $serviceName, $username, $password, $timeout = 10)
    {
        $this->host = $host;
        $this->port = $port;
        $this->serviceName = $serviceName;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->connect();
    }

    private function connect()
    {
        $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$this->host})(PORT={$this->port}))(CONNECT_DATA=(SERVICE_NAME={$this->serviceName})))";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => $this->timeout,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            $this->setSessionTimeout();
        } catch (PDOException $e) {
            throw new Exception("Erro ao conectar ao Oracle: " . $e->getMessage());
        }
    }

    private function setSessionTimeout()
    {
        $query = "ALTER SESSION SET RESOURCE_LIMIT = TRUE";
        $this->pdo->exec($query);
        
        $query = "ALTER SESSION SET IDLE_TIME = " . $this->timeout;
        $this->pdo->exec($query);
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro na consulta: " . $e->getMessage());
        }
    }

    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Erro na execução: " . $e->getMessage());
        }
    }

    public function paginate($sql, $params = [], $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $paginatedSql = "SELECT * FROM ( SELECT a.*, ROWNUM rnum FROM ( $sql ) a WHERE ROWNUM <= :limit ) WHERE rnum > :offset";
        
        try {
            $stmt = $this->pdo->prepare($paginatedSql);
            $stmt->bindValue(':limit', $offset + $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Erro na paginação: " . $e->getMessage());
        }
    }

    public function close()
    {
        $this->pdo = null;
    }
}
