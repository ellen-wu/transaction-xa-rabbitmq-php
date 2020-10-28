<?php

include "RabbitMqConfig.php";
include "DbConfig.php";
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @category 商品消息mq
 * @author Ellen
 * @date 2020
 * @description
 */
class GoodsMq
{
    private $pdo = null;

    public function __construct()
    {
        $dsn = DbConfig::$type . ':host=' . DbConfig::$host . ':' . DbConfig::$port . ';dbname=' . DbConfig::$dbname;
        $this->pdo = new PDO($dsn, DbConfig::$user, DbConfig::$password);
        $this->pdo->exec("set names utf8");
    }

    /**
     * 发送消息 库存已减 任务完成
     * @param  [type] $exchange   [description]
     * @param  [type] $routingKey [description]
     * @param  [type] $msgContent [description]
     * @return [type]             [description]
     */
    public function provider($exchange, $routingKey, $msgContent)
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
            $flag = true;
            $msgContentArray = json_decode($msg->body, true);
            $sql = "update `goods` set `stock` = `stock` - " . $msgContentArray['buy_number'] . " where id=" .
                $msgContentArray['goods_id'];

            // 接收需要减库存的消息 商品减库存，并且将任务写到商品任务表
            RECONNECT:
            if (!$this->pdo) {
                echo "mysql 连接已失效，重新连接！\n";

                $dsn = DbConfig::$type . ':host=' . DbConfig::$host . ':' . DbConfig::$port . ';dbname=' . DbConfig::$dbname;
                $this->pdo = new PDO($dsn, DbConfig::$user, DbConfig::$password);
                $this->pdo->exec("set names utf8");
            }

            // 检查是否减过库存 如果执行过减库存任务 不再执行减库存和插入任务表的操作 直接发送任务已经完后给mq
            $sqlExists = "select count(*) as c from `goods_cut_task` where `task_id` = " . $msgContentArray['task_id'];
            $query = $this->pdo->query($sqlExists);
            if (!$query) {
                $this->pdo = null;
                goto RECONNECT;
            } else {
                $row = $query->fetch();

                if (!empty($row) && $row['c'] > 0) {
                } else {
                    $this->pdo->beginTransaction();
                    // 减库存操作
                    $result = $this->pdo->exec($sql);
                    if ($result === false) {
                        // 获取错误信息数组
                        $errorArray = $this->pdo->errorInfo();
                        if (isset($errorArray[1]) && ($errorArray[1] == 2006 || $errorArray[1] == 2013)) {
                            $this->pdo = null;
                            goto RECONNECT;
                        }
                    } else {
                        // 记录到任务表 标明减库存任务已经完成
                        if ($result > 0) {
                            $sql = "insert into `goods_cut_task` (`order_id`, `task_id`, `task_type`, `version`, " .
                                "`mq_exchange`, `mq_routing_key`, `msg_content`, " . "`create_time`) values ('" .
                                $msgContentArray['order_id'] . "', '" . $msgContentArray['task_id'] . "', '" . 
                                $msgContentArray['task_type'] . "', '" . $msgContentArray['version'] . "', '" .
                                $msgContentArray['mq_exchange'] . "', '" . $msgContentArray['mq_routing_key'] . "','" .
                                $msg->body . "', '" . $timeNow . "')";

                            $result = $this->pdo->exec($sql);

                            if ($result > 0) {
                                $insertId = $this->pdo->lastInsertId();
                            } else {
                                $flag = false;
                            }
                        } else {
                            $flag = false;
                        }
                    }

                    if ($flag) {
                        $this->pdo->commit();
                    } else {
                        $this->pdo->rollBack();
                    }
                }
            }

            // 给订单服务发消息 减库存已经成功
            if ($flag) {
                $this->provider(
                    RabbitMqConfig::ORDER_GOODS_EXCHANGE,
                    RabbitMqConfig::ORDER_GOODS_CUT_STOCK_FINISH_KEY,
                    $msg->body
                );

                echo "任务[" . $msgContentArray['task_id'] . "]减库存完成！\n";
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
