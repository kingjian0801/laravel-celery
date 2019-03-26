<?php

namespace Kingjian0801\LaravelCelery;

class Celery extends CeleryAbstract
{
    /**
     * @param [type]  $host                [redis地址]
     * @param [type]  $password            [密码，]
     * @param [type]  $database               [指定redis库]
     * @param string  $exchange            [list集合名称]
     * @param integer $port                [端口号]
     * @param boolean $connector           [description]
     * @param boolean $persistent_messages [description]
     * @param integer $result_expire       [description]
     * @param array   $ssl_options         [description]
     */
    public function __construct($host, $password, $database, $exchange='celery', $port=6379, $persistent_messages=false, $result_expire=0, $ssl_options=[])
    {
        $broker_connection = [
            'host' => $host,
            'password' => $password,
            'database' => $database,
            'exchange' => $exchange,
            'binding' => $exchange,
            'port' => $port,
            'result_expire' => $result_expire,
            'ssl_options' => $ssl_options
        ];
        $backend_connection = $broker_connection;

        $items = $this->BuildConnection($broker_connection);
        $items = $this->BuildConnection($backend_connection, true);
    }
}