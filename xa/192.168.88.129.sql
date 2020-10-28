# 商品数据库 在服务器 192.168.88.129
set names utf8;
drop database `ellen_shop`;
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

insert into `goods` (`name`, `stock`) values ('华为Mate40', 5000), ('iphone 12', 2000), ('小米 10', 3000), ('realme Q2', 1000);
