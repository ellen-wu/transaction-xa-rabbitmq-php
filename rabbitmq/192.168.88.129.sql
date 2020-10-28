# 商品数据库 在服务器 192.168.88.129
set names utf8;
drop database if exists `ellen_shop`;
create database /*!32312 if not exists*/ `ellen_shop` /*!40100 default character set utf8 */;
use ellen_shop;
drop table if exists `goods`;
create table `goods` (
    `id` int unsigned auto_increment primary key,
    `name` varchar(210) not null default '' comment '商品名称',
    `stock` int unsigned not null default 0 comment '库存',
    `create_time` int unsigned not null default 0 comment '添加时间',
    `update_time` int unsigned not null default 0 comment '修改时间'
) engine=InnoDB charset=utf8 comment '商品表';

insert into `goods` (`name`, `stock`) values ('华为Mate40', 50000000), ('iphone 12', 20000000), ('小米 10', 30000000), ('realme Q2', 10000000);



# 商品服务的减库存 当减库存与增加任务记录成功后 向mq发送成功消息
drop table if exists `goods_cut_task`;
create table `goods_cut_task` (
    `id` int unsigned auto_increment primary key,
    `order_id` int unsigned not null default 0 comment '订单id',
    `task_id` int unsigned not null default 0 comment '减库存任务id',
    `task_type` tinyint not null default 0 comment '任务类型',
    `version` tinyint not null default 0 comment '乐观锁版本（也可以作为任务执行的次数，主要是在订单服务）',
    `status` tinyint not null default 0 comment '状态',
    `mq_exchange` varchar(120) not null default '' comment 'mq交换机',
    `mq_routing_key` varchar(120) not null default '' comment 'mq路由',
    `msg_content` varchar(600) not null default '' comment '收到的消息内容',
    `create_time` int unsigned not null default 0 comment '创建时间',
    `update_time` int unsigned not null default 0 comment '更新时间',
    key (`order_id`),
    key (`task_id`)
) engine=InnoDB charset=utf8 comment '减库存任务表';
