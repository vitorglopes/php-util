<?php

// namespace TO-DO ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPNoDataException;

class MessageQueue
{
    private $config;
    private $channel;
    private string $queue;
    private bool $durable;
    private AMQPStreamConnection $connection;

    public function __construct(string $queue, bool $durable = false)
    {
        $this->queue = $queue;
        $this->durable = $durable;

        // $this->config = TO-DO ;

        $this->connection = new AMQPStreamConnection(
/*       TO-DO
            $this->config["host"],
            $this->config["porta"],
            $this->config["user"],
            $this->config["passwd"]
*/
        );
    }

    public function connect(): void
    {
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, $this->durable, false, false);
    }

    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function publish(array $message): void
    {
        $message = new AMQPMessage(json_encode($message));
        $this->channel->basic_publish($message, "", $this->queue);
    }

    public function consume(callable $callback, bool $autoAck = false, string $consumer_tag = ""): void
    {
        try {
            $this->channel->basic_consume($this->queue, $consumer_tag, false, $autoAck, false, false, $callback);
            while ($this->channel->is_open()) {
                $this->channel->wait();
            }
        } catch (AMQPNoDataException $e) {
            return;
        }
    }
}
