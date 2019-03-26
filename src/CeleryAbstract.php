<?php

namespace Kingjian0801\LaravelCelery;

use PhpAmqpLib\Exception\AMQPProtocolConnectionException;

/**
 * Client for a Celery server - abstract base class implementing actual logic
 * @package celery-php
 */
abstract class CeleryAbstract
{
    private $broker_connection = null;
    private $broker_connection_details = [];
    private $broker_amqp = null;

    private $backend_connection = null;
    private $backend_connection_details = [];
    private $backend_amqp = null;

    private $isConnected = false;

    private function SetDefaultValues($details)
    {
        $defaultValues = ["host" => "", "password" => "", "database" => "", "exchange" => "celery", "port" => 6379, "persistent_messages" => false, "result_expire" => 0, "ssl_options" => []];

        $returnValue = [];

        foreach (['host', 'password', 'database', 'exchange', 'port', 'persistent_messages', 'result_expire', 'ssl_options'] as $detail) {
            if (!array_key_exists($detail, $details)) {
                $returnValue[$detail] = $defaultValues[$detail];
            } else {
                $returnValue[$detail] = $details[$detail];
            }
        }
        return $returnValue;
    }

    public function BuildConnection($connection_details, $is_backend=false)
    {
        $connection_details = $this->SetDefaultValues($connection_details);
        $ssl = !empty($connection_details['ssl_options']);

        $amqp = new RedisConnector();
        $connection = self::InitializeAMQPConnection($connection_details);
        $amqp->Connect($connection);

        if ($is_backend) {
            $this->backend_connection_details = $connection_details;
            $this->backend_connection = $connection;
            $this->backend_amqp = $amqp;
        } else {
            $this->broker_connection_details = $connection_details;
            $this->broker_connection = $connection;
            $this->broker_amqp = $amqp;
        }
    }

    public static function InitializeAMQPConnection($details)
    {
        $amqp = new RedisConnector();
        try {
            return $amqp->GetConnectionObject($details);
        }
        catch (AMQPProtocolConnectionException $e) {
            throw new CeleryConnectionException("Failed to establish a AMQP connection. Check credentials.");
        }
        catch (\ErrorException $e) {
            throw new CeleryConnectionException("Failed to establish a AMQP connection. Check hostname.");
        }
        catch (Exception $e) {
            throw new CeleryConnectionException("Failed to establish a AMQP connection. Unspecified failure.");
        }
    }

    public function PostTask($task, $args, $async_result=true, $routing_key="celery", $task_args=[])
    {
        if (!is_array($args)) {
            throw new CeleryException("Args should be an array");
        }

        if (!$this->isConnected) {
            $this->broker_amqp->Connect($this->broker_connection);
            $this->isConnected = true;
        }

        $id = uniqid('php_', true);

        if (array_keys($args) === range(0, count($args) - 1)) {
            $kwargs = [];
        }

        else {
            $kwargs = $args;
            $args = [];
        }

        $task_array = array_merge(
            [
                'id' => $id,
                'task' => $task,
                'args' => $args,
                'kwargs' => (object)$kwargs,
            ],
            $task_args
        );

        $task = json_encode($task_array);
        $params = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'immediate' => false,
        ];

        if ($this->broker_connection_details['persistent_messages']) {
            $params['delivery_mode'] = 2;
        }

        $this->broker_connection_details['routing_key'] = $routing_key;

        $success = $this->broker_amqp->PostToExchange(
            $this->broker_connection,
            $this->broker_connection_details,
            $task,
            $params
        );

        if (!$success) {
            throw new CeleryPublishException();
        }

        if ($async_result) {
            return new AsyncResult($id, $this->backend_connection_details, $task_array['task'], $args);
        } else {
            return true;
        }
    }

    public function getAsyncResultMessage($taskName, $taskId, $args = null, $removeMessageFromQueue = true)
    {
        $result = new AsyncResult($taskId, $this->backend_connection_details, $taskName, $args);

        $messageBody = $result->amqp->GetMessageBody(
            $result->connection,
            $taskId,
            $this->backend_connection_details['result_expire'],
            $removeMessageFromQueue
        );

        return $messageBody;
    }
}