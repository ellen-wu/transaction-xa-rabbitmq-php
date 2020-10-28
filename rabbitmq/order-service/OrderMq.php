<?php

include "RabbitMqConfig.php";
include "DbConfig.php";
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @category 订单消息mq
 * @author Ellen
 * @date 2020
 * @description
 */
class OrderMq
{
    private $pdo = null;

    public function __construct()
    {
        $dsn = DbConfig::$type . ':host=' . DbConfig::$host . ':' . DbConfig::$port . ';dbname=' . DbConfig::$dbname;
        $this->pdo = new PDO($dsn, DbConfig::$user, DbConfig::$password);
        $this->pdo->exec("set names utf8");
    }

    /**
     * 发送消息
     * @param  [type] $exchange   [description]
     * @param  [type] $routingKey [description]
     * @param  [type] $msgContent [description]
     * @return [type]             [description]
     */
    public static function provider($exchange, $routingKey, $msgContent)
    {
        $connection = new AMQPStreamConnection(
            RabbitMqConfig::MQ_HOST,
            RabbitMqConfig::MQ_PORT,
            RabbitMqConfig::MQ_USER,
            RabbitMqConfig::MQ_PASSWORD
        );

        $channel = $connection->channel();

        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $msg = new AMQPMessage($msgContent);

        $channel->basic_publish($msg, $exchange, $routingKey);

        $channel->close();
        $connection->close();
    }

    /**
     * 接收消息
     * @param  [type] $exchange   [description]
     * @param  [type] $routingKey [description]
     * @param  string $queue      [description]
     * @return [type]             [description]
     */
    public function consumer($exchange, $routingKey, $queue = '')
    {
        $connection = new AMQPStreamConnection(
            RabbitMqConfig::MQ_HOST,
            RabbitMqConfig::MQ_PORT,
            RabbitMqConfig::MQ_USER,
            RabbitMqConfig::MQ_PASSWORD
        );

        $channel = $connection->channel();
        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $channel->queue_declare($queue, false, false, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);

        $callback = function ($msg) {
            $timeNow = time();
            $msgContentArray = json_decode($msg->body, true);
            // 接收完成的消息 将订单任务记录移到订单历史任务表
            $taskId = $msgContentArray['task_id'];

            $sql = "select * from `order_cut_task` where id=" . $taskId;

            $query = $this->pdo->query($sql);
            if ($row = $query->fetch()) {
                $flag = true;
                $this->pdo->beginTransaction();
                // 插入历史表
                $sqlInsert = "insert into `order_cut_task_history` (`order_id`, `task_id`, `task_type`, `version`, " .
                    "`mq_exchange`, `mq_routing_key`, " . "`msg_content`, " . "`create_time`, `update_time`) " .
                    "values (" . $row['order_id'] .", '" . $taskId . "', '" . $row['task_type'] . "', '" . 
                    $row['version'] . "', '" . $row['mq_exchange'] . "', '" . $row['mq_routing_key'] .
                    "', '" . $msg->body . "', '" . $timeNow . "', '" . $timeNow . "')";

                $result = $this->pdo->exec($sqlInsert);

                if ($result > 0) {
                } else {
                    $flag = false;
                }

                if ($flag) {
                    // 从任务表删除
                    $sqlDelete = "delete from `order_cut_task` where id=" . $taskId;

                    $result = $this->pdo->exec($sqlDelete);
                    if ($result > 0) {
                    } else {
                        $flag = false;
                    }
                }

                // 执行成功，提交事务
                if ($flag) {
                    $this->pdo->commit();
                } else {
                    $this->pdo->rollBack();
                }

                if ($flag) {
                    echo "任务[" . $taskId . "]完成了！\n";
                }
            }
        };

        $channel->basic_consume($queue, '', false, true, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
