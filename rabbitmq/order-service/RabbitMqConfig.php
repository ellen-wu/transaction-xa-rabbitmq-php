<?php

/**
 * @category rabbitmq的配置类
 * @author Ellen
 * @date 2020
 * @description
 */

class RabbitMqConfig
{
    const MQ_HOST = '192.168.88.129';
    const MQ_PORT = '5672';
    const MQ_USER = 'guest';
    const MQ_PASSWORD = 'guest';

    // 订单减库存任务 交换机
    const ORDER_GOODS_EXCHANGE = 'order_goods_exchange';

    // 订单减库存任务 减库存路由key  由订单服务向mq发送消息
    const ORDER_GOODS_CUT_STOCK_KEY = 'order_goods_cut_stock_key';
    // 订单减库存任务 减库存队列  由订单服务向mq发送消息
    const ORDER_GOODS_CUT_STOCK_QUEUE = 'order_goods_cut_stock_queue';

    // 订单减库存任务完成 减库存完成路由key  由商品服务向mq发送消息
    const ORDER_GOODS_CUT_STOCK_FINISH_KEY = 'order_goods_cut_stock_finish_key';
    // 订单减库存任务完成 减库存完成队列  由商品服务向mq发送消息
    const ORDER_GOODS_CUT_STOCK_FINISH_QUEUE = 'order_goods_cut_stock_finish_queue';
}
