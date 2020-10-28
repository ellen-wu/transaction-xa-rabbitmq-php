<?php

include "DbConfig.php";
include "RabbitMqConfig.php";

/**
 * @category 订单类
 * @author Ellen
 * @date 2020
 * @description 主要用于测试 模拟下单成功后，减库存操作，这里只是模拟一个订单一个商品，实际中一个订单可能多个商品
 */
class Order
{
    private $pdo = null;

    public function __construct()
    {
        $dsn = DbConfig::$type . ':host=' . DbConfig::$host . ':' . DbConfig::$port . ';dbname=' . DbConfig::$dbname;
        $this->pdo = new PDO($dsn, DbConfig::$user, DbConfig::$password);
        $this->pdo->exec("set names utf8");
    }


    public function makeOrder()
    {
        // sql执行状态
        $flag = true;

        $timeNow = time();
        // 随机商品id
        $goodsId = mt_rand(1, 4);
        // 随机购买数量
        $buyNumber = mt_rand(1, 10);
        // 订单号
        $orderNo = date("YmdHis", $timeNow) . mt_rand(1000000, 9999999);

        // 订单插入sql
        $sql = "insert into `order` (`order_no`, `goods_id`, `buy_number`, `create_time`) values ('" . $orderNo . 
            "', '" . $goodsId . "', '" . $buyNumber . "', '" . $timeNow . "');";

        $this->pdo->beginTransaction();

        $orderId = 0;
        $result = $this->pdo->exec($sql);
        if ($result > 0) {
            $orderId = $this->pdo->lastInsertId();
        } else {
            $flag = false;
        }

        // 订单插入成功后，插入任务表
        if ($flag) {
            // 消息体 这里没有任务id 在订单任务中加入，其实也可以使用订单id，订单id与任务id是在一个事务中的
            $msgContentArray = [];
            $msgContentArray['order_id'] = $orderId;
            $msgContentArray['goods_id'] = $goodsId;
            $msgContentArray['buy_number'] = $buyNumber;

            $msgContent = json_encode($msgContentArray);

            $sql = "insert into `order_cut_task` (`order_id`, `task_type`, `mq_exchange`, `mq_routing_key`, " .
                "`msg_content`, " . "`create_time`, `update_time`) values (" . $orderId .", '1', '" .
                RabbitMqConfig::ORDER_GOODS_EXCHANGE . "', '" . RabbitMqConfig::ORDER_GOODS_CUT_STOCK_KEY . "', '" .
                $msgContent . "', '" . $timeNow . "', '" . $timeNow . "')";

            $result = $this->pdo->exec($sql);

            if ($result > 0) {
                $taskId = $this->pdo->lastInsertId();
            } else {
                $flag = false;
            }
        }

        if ($flag) {
            $this->pdo->commit();
        } else {
            $this->pdo->rollBack();
        }

        return $flag;
    }
}


$order = new Order();
$result = $order->makeOrder();

if ($result) {
    echo "订单插入成功，接下来需要减库存！\n";
}
