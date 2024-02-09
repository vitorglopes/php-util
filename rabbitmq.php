<?php

require_once "vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

echo " >>> RUNNING ... \n";

$targetQueue = $argv[1] ?? "";
$type = $argv[2] ?? "";
$message = $argv[3] ?? "";

if (empty($targetQueue)) {
    echo " [x] Incorrect arg1: Define a target queue.\n";
    exit;
}

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare($targetQueue, false, true, false, false);

switch ($type) {

    default:
        echo " [x] Incorrect arg2: Use 'producer' or 'consumer'. \n";
        break;

    case "producer":
        if (empty($message)) {
            echo " [x] Incorrect arg3: Define a message. \n";
            exit;
        }
        $message = new AMQPMessage($message);
        $channel->basic_publish($message, "", $targetQueue);
        echo " [v] Sent message! \n";
        break;

    case "consumer":
        $callback = function ($msg) {
            echo ' --- MSG: ', $msg->body, "\n";
        };

        $channel->basic_consume($targetQueue, '', false, true, false, false, $callback);

        while ($channel->is_open()) {
            $channel->wait();
        }
        break;
}
$channel->close();
$connection->close();

echo "... END <<< \n";
