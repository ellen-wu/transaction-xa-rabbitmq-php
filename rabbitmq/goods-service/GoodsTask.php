<?php

include 'GoodsMq.php';
/**
 * @category 减库存任务消费者
 * @author Ellen
 * @date 2020
 * @description
 */

$goodsMq = new GoodsMq();

$goodsMq->consumer(
    RabbitMqConfig::ORDER_GOODS_EXCHANGE,
    RabbitMqConfig::ORDER_GOODS_CUT_STOCK_KEY,
    RabbitMqConfig::ORDER_GOODS_CUT_STOCK_QUEUE
);
