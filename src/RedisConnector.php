<?php

namespace Kingjian0801\LaravelCelery;

class RedisConnector
{
    public $content_type = 'application/json';

    public $celery_result_prefix = 'celery-task-meta-';

    protected function GetHeaders()
    {
        return new \stdClass;
    }

    protected function GetMessage($task)
    {
        $result = [];
        $result['body'] = base64_encode($task);
        $result['headers'] = $this->GetHeaders();
        $result['content-type'] = $this->content_type;
        $result['content-encoding'] = 'binary';
        return $result;
    }

    protected function GetDeliveryMode($params=[])
    {
        if (isset($params['delivery_mode'])) {
            return $params['delivery_mode'];
        }
        return 2;
    }

    protected function ToStr($var)
    {
        return json_encode($var);
    }

    protected function ToDict($raw_json)
    {
        return json_decode($raw_json, true);
    }

    public function PostToExchange($connection, $details, $task, $params)
    {
        $connection = $this->Connect($connection);
        $body = json_decode($task, true);
        $message = $this->GetMessage($task);
        $message['properties'] = [
            'body_encoding' => 'base64',
            'reply_to' => $body['id'],
            'delivery_info' => [
                'priority' => 0,
                'routing_key' => $details['exchange'],
                'exchange' => $details['exchange'],
            ],
            'delivery_mode' => $this->GetDeliveryMode($params),
            'delivery_tag'  => $body['id']
        ];
        $connection->lPush($details['exchange'], $this->ToStr($message));

        return true;
    }

    public function Connect($connection)
    {
        if ($connection->isConnected()) {
            return $connection;
        } else {
            $connection->connect();
            return $connection;
        }
    }

    protected function GetResultKey($task_id)
    {
        return sprintf("%s%s", $this->celery_result_prefix, $task_id);
    }

    protected function FinalizeResult($connection, $task_id)
    {
        if ($connection->exists($this->GetResultKey($task_id))) {
            $connection->del($this->GetResultKey($task_id));
            return true;
        }

        return false;
    }

    public function GetMessageBody($connection, $task_id, $expire=0, $removeMessageFromQueue=true)
    {
        $result = $connection->get($this->GetResultKey($task_id));
        if ($result) {
            $redis_result = $this->ToDict($result, true);
            $result = json_encode($redis_result);
            if ($removeMessageFromQueue) {
                $this->FinalizeResult($connection, $task_id);
            }

            return $result;
        } else {
            return false;
        }
    }

    public function GetConnectionObject($details)
    {
        $connect = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => $details['host'],
            'port'   => $details['port'],
            'database' => $details['database'],
            'password' => empty($details['password']) ? null : $details['password']
        ]);
        return $connect;
    }
}