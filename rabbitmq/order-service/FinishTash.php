<?php

include 'OrderMq.php';
/**
 * @category 订单减库存任务完成的消费者
 * @author Ellen
 * @date 2020
 * @description 接收商品减库存完成的消息，将本地的任务消息转移到任务历史表中
 */

$task = new OrderMq();

$task->consumer(
    RabbitMqConfig::ORDER_GOODS_EXCHANGE,
    RabbitMqConfig::ORDER_GOODS_CUT_STOCK_FINISH_KEY,
    RabbitMqConfig::ORDER_GOODS_CUT_STOCK_FINISH_QUEUE
);
