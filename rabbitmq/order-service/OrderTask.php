<?php

include 'OrderMq.php';

/**
 * @category 订单发送减库存给mq
 * @author Ellen
 * @date 2020
 * @description
 */

class OrderTask
{
    private $pdo = null;

    public function __construct()
    {
        $dsn = DbConfig::$type . ':host=' . DbConfig::$host . ':' . DbConfig::$port . ';dbname=' . DbConfig::$dbname;
        $this->pdo = new PDO($dsn, DbConfig::$user, DbConfig::$password);
        $this->pdo->exec("set names utf8");
    }

    public function task()
    {
        $timeNow = time();

        // 这里只是为了方便测试 实际可以是一分钟之前的任务
        $timeAgo = $timeNow - 86400;
        // 这里的查询任务 请根据实际情况进行
        $sql = "select * from `order_cut_task` where `update_time` >= " . $timeAgo;

        foreach ($this->pdo->query($sql) as $row) {
            if ($this->_getTask($row['id'], $row['version'])) {
                // 消息体重组
                $msgContentArray = json_decode($row['msg_content'], true);
                $msgContentArray['task_id'] = $row['id'];
                $msgContentArray['task_type'] = $row['task_type'];
                $msgContentArray['mq_exchange'] = $row['mq_exchange'];
                $msgContentArray['mq_routing_key'] = $row['mq_routing_key'];
                $msgContentArray['version'] = $row['version'];

                $msgContent = json_encode($msgContentArray);
                OrderMq::provider(
                    $msgContentArray['mq_exchange'],
                    $msgContentArray['mq_routing_key'],
                    $msgContent
                );

                // 更新任务时间
                $sqlUpdate = "update `order_cut_task` set `update_time` = " . $timeNow . " where id=" . $row['id'];
        
                $result = $this->pdo->exec($sqlUpdate);
            }
        }
    }

    /**
     * 根据任务id和版本更新版本号
     * @param  [type] $taskId  [description]
     * @param  [type] $version [description]
     * @return [type]          [description]
     */
    public function _getTask($taskId , $version)
    {
        $sqlUpdate = "update `order_cut_task` set `version` = `version` + 1 where id=" . $taskId .
            ' and `version` = ' . $version;
        
        $result = $this->pdo->exec($sqlUpdate);
        if ($result === false) {
            return false;
        }

        if ($result > 0) {
            return true;
        }
        return false;
    }
}


$task = new OrderTask();
$task->task();
