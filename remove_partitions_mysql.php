<?php
/*
List MySQL Partitions:

SELECT TABLE_NAME, COUNT(*) AS TOTAL_PARTITIONS
FROM information_schema.PARTITIONS
WHERE TABLE_SCHEMA = 'database_name'
GROUP BY 1
HAVING COUNT(*) > 1
ORDER BY 2 DESC;

*/

$host = 'localhost';
$user = 'root';
$password = '123456';
$database = 'database_name';
$port = '3306';

$target_table_name = 'table_name';

$db_connection = new mysqli($host, $user, $password, $database, $port);

$sql = 
"SELECT TABLE_NAME, TABLE_ROWS, PARTITION_NAME 
FROM information_schema.PARTITIONS 
WHERE TABLE_SCHEMA = '$database' 
AND TABLE_NAME = '$target_table_name' 
AND PARTITION_NAME IS NOT NULL 
AND PARTITION_NAME <> '' ";

echo "= STARTING..." . PHP_EOL;

$result = mysqli_query($db_connection, $sql);
$partitions = [];

while ($data = mysqli_fetch_assoc($result)) {
    $partitions[] = $data['PARTITION_NAME'];
}

$length = count($partitions);
$limit = $length - 1;

for ($i = 0; $i < $limit; $i++) {

    $sqlPartition = "ALTER TABLE $target_table_name DROP PARTITION $partitions[$i] ";
    $result = mysqli_execute_query($db_connection, $sqlPartition);

    if ($result) {
        echo $sqlPartition . PHP_EOL;
    }
}

echo "= END" . PHP_EOL;
