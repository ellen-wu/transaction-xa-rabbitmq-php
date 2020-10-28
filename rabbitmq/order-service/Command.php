<?php

/**
 * @category
 * @author Ellen
 * @date 2020
 * @description
 */

// 由于用的windows测试 写个简单的进程充当定时

$i = 1;
while (true) {
    exec("php D:/www/transaction-test/rabbitmq/order-service/OrderTask.php");

    echo "执行订单减库存任务: ", ++$i, "\n";
    sleep(3);
}
