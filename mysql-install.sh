#!/bin/bash


# 搜狐源
# http://mirrors.sohu.com/mysql/MySQL-5.7/mysql-5.7.28-linux-glibc2.12-x86_64.tar.gz

# 163 本地测试 速度 比 搜狐 快些
# http://mirrors.163.com/mysql/Downloads/MySQL-5.7/mysql-5.7.28-linux-glibc2.12-x86_64.tar.gz
function mysql_install()
{
    cd /usr/local/src/

    MYSQL_VERSION="mysql-5.7.28-linux-glibc2.12-x86_64"
    # mysql
    wget http://mirrors.163.com/mysql/Downloads/MySQL-5.7/${MYSQL_VERSION}.tar.gz

    if [ "$?" = "0" ];then
        echo "mysql download        OK"
    else
        echo "mysql download     FAILD"
    fi

    # add mysql user
    groupadd mysql
    useradd mysql -s /sbin/nologin -g mysql -M

    echo "mysql configure     START"

    tar xf /usr/local/src/${MYSQL_VERSION}.tar.gz
    cp -R /usr/local/src/${MYSQL_VERSION} /usr/local/mysql
    cd /usr/local/mysql/

    chown -R mysql.mysql /usr/local/mysql

    # /usr/local/mysql/scripts/mysql_install_db --basedir=/usr/local/mysql --datadir=/usr/local/mysql/data --user=mysql
    # 5.7 以后 初始化 方式不同
    /usr/local/mysql/bin/mysqld --initialize-insecure --basedir=/usr/local/mysql --datadir=/usr/local/mysql/data --user=mysql

    # 5.7.18 以后没有配置文件
    echo -e "[mysqld]\nbasedir = /usr/local/mysql\ndatadir = /usr/local/mysql/data\nport = 3306\nserver_id = 1\nsocket = /tmp/mysql.sock\nlog-bin=mysql-bin\nslow-query-log=1\nlong_query_time = 1\nslow-query-log-file=/usr/local/mysql/slow-query.log\n\nsql_mode=NO_ENGINE_SUBSTITUTION\n" > /etc/my.cnf

    cp /usr/local/mysql/support-files/mysql.server /etc/init.d/mysqld
    chmod +x /etc/init.d/mysqld

    echo "mysql configure     END"

    echo "/usr/local/lib\n/usr/local/mysql/lib" > /etc/ld.so.conf.d/libc.conf
    ldconfig

    cd /usr/local/src/
}

if [ ! -d "/usr/local/mysql/" ]; then
    mysql_install
fi
