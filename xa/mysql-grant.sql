# 授权sql 如果是使用的mysql8.0，那么请先创建用户，再授权
# 192.168.88.129 192.168.88.130 都执行
# 如需配置自己的用户和密码，请自行修改
GRANT ALL PRIVILEGES ON *.* TO 'ellen'@'%' IDENTIFIED BY 'ellen' WITH GRANT OPTION;   
FLUSH   PRIVILEGES;
