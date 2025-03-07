<?php

use PDO;
use PDOException;
use Exception;

/**
 * Class for connecting to the Oracle database
 * 
 */
class OracleDB
{
    private $host;
    private $port;
    private $username;
    private $psswrd;
    private $serviceName;
    private $pdo;
    private $options;
    private $timeout;
    private static $timeoutDefault = 15;

    /**
     * @param string $host
     * @param string $port
     * @param string $username
     * @param string $psswrd
     * @param string $serviceName Service name or Database name
     * @param integer $timeout Timeout em segundos
     */
    public function __construct(?string $host, ?string $port, ?string $username, ?string $psswrd, ?string $serviceName, ?int $timeout = 0)
    {
        $this->setTimeout($timeout);
        $this->setOptions();

        if (isset($host) && !empty($host)) {
            $this->setNewConnection($host, $port, $username, $psswrd, $serviceName, $timeout);
        }
    }

    private function setTimeout(int $timeout = 0): void
    {
        if ($timeout > 0) {
            $this->timeout = $timeout;
            return;
        }
        $this->timeout = self::$timeoutDefault;
    }

    private function setOptions(): void
    {
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => $this->timeout,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    }

    public function setNewConnection(string $host, string $port, string $username, string $psswrd, string $serviceName, int $timeout = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->psswrd = $psswrd;
        $this->serviceName = $serviceName;
        $this->setTimeout($timeout);
        $this->setOptions();
    }

    private function connect(): void
    {
        $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$this->host})(PORT={$this->port}))(CONNECT_DATA=(SERVICE_NAME={$this->serviceName})))";

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->psswrd, $this->options);
        } catch (PDOException $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }
    }

    private function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Execute a query on the database
     *
     * @param string $sql
     * @param array $params
     * @return void
     */
    public function query(string $sql, array $params = [])
    {
        try {
            $this->connect();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->close();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->close();
            throw new Exception("Query error: " . $e->getMessage());
        }
    }

    /**
     * Paginate the results of a query
     *
     * @param string $sql
     * @param array $params
     * @param integer $page
     * @param integer $perPage
     * @return void
     */
    public function paginate(string $sql, $params = [], $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $paginatedSql = "SELECT * FROM ( SELECT a.*, ROWNUM rnum FROM ( $sql ) a WHERE ROWNUM <= :limit ) WHERE rnum > :offset ";

        try {
            $this->connect();
            $stmt = $this->pdo->prepare($paginatedSql);
            $stmt->bindValue(':limit', $offset + $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $this->close();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->close();
            throw new Exception("Paginate error: " . $e->getMessage());
        }
    }
}
