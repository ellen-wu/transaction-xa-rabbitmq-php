# 基于XA协议实现分布式事务
基于XA协议，简单的模拟下单减库存


详细信息，参考：[官方文档](https://dev.mysql.com/doc/refman/8.0/en/xa.html)

测试代码参考，官方[XA Transaction SQL Statements](https://dev.mysql.com/doc/refman/8.0/en/xa-statements.html)实现

-------
### 测试步骤

1、首先，配置2台不同MySql5.7服务器，一台作为商品服务（192.168.88.129），另一台作为订单服务（192.168.88.130）

2、分别执行xa目录下的sql文件，我测试的配置是：

    服务器：192.168.88.129 商品服务 
        192.168.88.129.sql
    服务器：192.168.88.130 订单服务
        192.168.88.130.sql
    采用同样的授权：
        mysql-grant.sql

3、运行 php xa-test.php 查看结果

