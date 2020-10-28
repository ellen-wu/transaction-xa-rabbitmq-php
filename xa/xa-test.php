<?php

/**
 * @category 分布式事务 基于xa实现
 * @author Ellen
 * @date 2020
 * @description 
 * 参考地址：https://dev.mysql.com/doc/refman/8.0/en/xa-statements.html
 * https://dev.mysql.com/doc/refman/8.0/en/xa-states.html
 * https://dev.mysql.com/doc/refman/8.0/en/xa-restrictions.html
 * 
 * https://www.php.net/manual/zh/mysqlnd-ms.quickstart.xa_transactions.php
 *
 */

// 数据库配置 129
$pdoGoodsConfig = [
    'host' => '192.168.88.129',
    'port' => 3306,
    'db' => 'ellen_shop',
    'db_user' => 'ellen',
    'db_pwd' => 'ellen'
];

// 数据库配置 130
$pdoOrderConfig = [
    'host' => '192.168.88.130',
    'port' => 3306,
    'db' => 'ellen_shop',
    'db_user' => 'ellen',
    'db_pwd' => 'ellen'
];

$pdoGoods = new PDO(
    "mysql:host=" . $pdoGoodsConfig['host'] . ';dbname=' . $pdoGoodsConfig['db'],
    $pdoGoodsConfig['db_user'],
    $pdoGoodsConfig['db_pwd']
);


$pdoOrder = new PDO(
    "mysql:host=" . $pdoOrderConfig['host'] . ';dbname=' . $pdoOrderConfig['db'],
    $pdoOrderConfig['db_user'],
    $pdoOrderConfig['db_pwd']
);

// 设置字符集
$sql = 'set names utf8';
$pdoGoods->exec($sql);
$pdoOrder->exec($sql);

// 商品id 随机
$goodsId = mt_rand(1, 4);
// 购买数量 随机
$buyNumber = mt_rand(1, 10);

// 订单号
$orderNo = date("YmdHis", time()) . mt_rand(1000000, 9999999);

// 事务id
$transIdOne = uniqid() . mt_rand(0, 100);
$transIdTwo = uniqid() . mt_rand(101, 200);

// 查询减库存之前的库存信息
$query = $pdoGoods->query("select * from `goods` where id=" . $goodsId);
$row = $query->fetch(PDO::FETCH_ASSOC);

// 订单sql
$sqlStartOne = 'XA START ' . "'" . $transIdOne . "'";
$sqlPrepareOne = 'XA PREPARE ' . "'" . $transIdOne . "'";
$sqlEndOne = 'XA END ' . "'" . $transIdOne . "'";
$sqlCommitOne = 'XA COMMIT ' . "'" . $transIdOne . "'";
$sqlRollbackOne = 'XA ROLLBACK ' . "'" . $transIdOne . "'";

// 商品sql
$sqlStartTwo = 'XA START ' . "'" . $transIdTwo . "'";
$sqlPrepareTwo = 'XA PREPARE ' . "'" . $transIdTwo . "'";
$sqlEndTwo = 'XA END ' . "'" . $transIdTwo . "'";
$sqlCommitTwo = 'XA COMMIT ' . "'" . $transIdTwo . "'";
$sqlRollbackTwo = 'XA ROLLBACK ' . "'" . $transIdTwo . "'";

// 操作成功标识
$flagGoodsUpdate = false;
$flagOrderInsert = false;

// 1、准备事务
$pdoOrder->query($sqlStartOne);
$pdoGoods->query($sqlStartTwo);

try {

    // 2、插入订单表
    $sql = "insert into `order` (`order_no`, `goods_id`, `buy_number`, `create_time`) values ('" . $orderNo . "', '" .
        $goodsId . "', '" . $buyNumber . "', '" . time() . "');";


    $result = $pdoOrder->query($sql);

    if (false === $result) {
        echo "订单插入失败！\n";
    } else {
        // 受影响的行数
        if ($result->rowCount() > 0) {
            // 成功通知准备提交
            $pdoOrder->query($sqlEndOne);
            $pdoOrder->query($sqlPrepareOne);

            $flagOrderInsert = true;
        }
    }

    if ($flagOrderInsert) {
        $sql = "update `goods` set `stock` = `stock` - " . $buyNumber . " where id = " . $goodsId;
        $result = $pdoGoods->query($sql);
        if (false === $result) {
            echo "商品库存更新失败！\n";
        } else {
            // 受影响的行数
            if ($result->rowCount() > 0) {
                // 成功通知准备提交
                $pdoGoods->query($sqlEndTwo);
                $pdoGoods->query($sqlPrepareTwo);

                $flagGoodsUpdate = true;
            }
        } 
    }

    // 判断状态 如果都成功 则都提交事务 否则回滚事务
    if ($flagOrderInsert && $flagGoodsUpdate) {
        $pdoOrder->query($sqlCommitOne);
        $pdoGoods->query($sqlCommitTwo);

        echo "执行成功！\n";
    } else {
        $pdoOrder->query($sqlRollbackOne);
        $pdoGoods->query($sqlRollbackTwo);
    }
} catch (Exception $e) {
    $pdoOrder->query($sqlRollbackOne);
    $pdoGoods->query($sqlRollbackTwo);
}


echo "---------------------- 修改前库存: " . $row['stock'] . " ----------------------\n";
echo "-------------------------  减库存: " . $buyNumber . "    ----------------------\n";
// print_r($row);

$query = $pdoGoods->query("select * from `goods` where id=" . $goodsId);

$rowNew = $query->fetch(PDO::FETCH_ASSOC);

echo "---------------------- 修改后库存: " . $rowNew['stock'] . " ----------------------\n";
// print_r($rowNew);

