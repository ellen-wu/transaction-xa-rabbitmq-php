# 订单数据库 在服务器 192.168.88.130
set names utf8;
drop database if exists `ellen_shop`;
create database /*!32312 if not exists*/ `ellen_shop` /*!40100 default character set utf8 */;
use ellen_shop;
drop table if exists `order`;
create table `order` (
    `id` int unsigned auto_increment primary key,
    `order_no` varchar(32) not null default '' comment '订单号',
    `out_order_no` varchar(32) not null default '' comment '外部订单号',
    `goods_id` int unsigned not null default 0 comment '商品id',
    `buy_number` int unsigned not null default 0 comment '购买数量',
    `create_time` int unsigned not null default 0 comment '订单创建时间',
    `status` tinyint not null default 0 comment '状态',
    key (`goods_id`)
) engine=InnoDB charset=utf8 comment '订单表'; 

# 订单服务减库存 当完成后会删除记录，将服务转向订单任务历史记录表
drop table if exists `order_cut_task`;
create table `order_cut_task` (
    `id` int unsigned auto_increment primary key comment '主键（任务id）',
    `order_id` int unsigned not null default 0 comment '订单id',
    `task_type` tinyint not null default 0 comment '任务类型',
    `version` tinyint not null default 0 comment '乐观锁版本（也可以作为任务执行的次数，主要是在订单服务）',
    `status` tinyint not null default 0 comment '状态',
    `mq_exchange` varchar(120) not null default '' comment 'mq交换机',
    `mq_routing_key` varchar(120) not null default '' comment 'mq路由',
    `msg_content` varchar(600) not null default '' comment '收到的消息内容',
    `error_msg` varchar(600) not null default '' comment '错误信息',
    `create_time` int unsigned not null default 0 comment '创建时间',
    `update_time` int unsigned not null default 0 comment '更新时间',
    key (`order_id`)
) engine=InnoDB charset=utf8 comment '订单减库存任务表';

drop table if exists `order_cut_task_history`;
create table `order_cut_task_history` (
    `id` int unsigned auto_increment primary key comment '主键',
    `order_id` int unsigned not null default 0 comment '订单id',
    `task_id` int unsigned not null default 0 comment '减库存任务id',
    `task_type` tinyint not null default 0 comment '任务类型',
    `version` tinyint not null default 0 comment '乐观锁版本（也可以作为任务执行的次数，主要是在订单服务）',
    `status` tinyint not null default 0 comment '状态',
    `mq_exchange` varchar(120) not null default '' comment 'mq交换机',
    `mq_routing_key` varchar(120) not null default '' comment 'mq路由',
    `msg_content` varchar(600) not null default '' comment '收到的消息内容',
    `error_msg` varchar(600) not null default '' comment '错误信息',
    `create_time` int unsigned not null default 0 comment '创建时间',
    `update_time` int unsigned not null default 0 comment '更新时间',
    key (`order_id`),
    key (`task_id`)
) engine=InnoDB charset=utf8 comment '订单减库存历史任务记录表';

