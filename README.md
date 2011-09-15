======
auto xhprof
======

    基于Facebook的XHPorf，通过PHP.ini中auto_prepend_file机制全局加载自动性能分析和保存模块。
    并把分析的数据存入MySQL中供后期查询。
    如果服务器安装并支持Gearman模块，分析后数据则后台异步保存到数据库中。


文件列表说明：

    auto-xhprof.php         全局加载文件。
    auto-xhprof-config.php  全局配置文件，设置MySQL数据库和参数等。
    gearman-worker.php      gearman后台处理worker进程。
    web/                    web显示目录，xhprof列表页面和原xhprof展示部分
    xhprof_lib/             xhprof库文件。


php.ini配置：

    ;php.ini
    auto_prepend_file = '/path/prepend.php'

    ;prepend.php
    <?php
       include_once '/path/auto-xhprof.php';
    ?>


auto-xhprof-config.php中常量的定义，根据实际情况进行修改：

    define('__XHPROF_AUTO_START',     true); // 是否自动打开全局xhprof
    define('__XHPROF_SAVE_TIMEOUT',   2);    // 超过几秒自动保存数据到MySQL

    define('__XHPROF_MYSQL_HOST',     '127.0.0.1'); // MySQL 主机
    define('__XHPROF_MYSQL_USER',     'root');      // MySQL 用户名
    define('__XHPROF_MYSQL_PASS',     '123456');    // MySQL 账户密码
    define('__XHPROF_MYSQL_DB',       'xhprof');    // 表名


Gearman定义：__XHPROF_GERAMAN_SERVERS：

    define('__XHPROF_GERAMAN_SERVERS', '127.0.0.1:4730;127.0.0.1:4730'); // gearman 服务器定义

    shell> $ gearmand -vvv -q libdrizzle --libdrizzle-host=127.0.0.1\
       --libdrizzle-user=root --libdrizzle-password=123456 --libdrizzle-db=gearman\
       --libdrizzle-table=queue --libdrizzle-mysql

    shell> $ php gearman-worker.php


页面显示修改index.php/callgraph.php中$xhprof_runs_impl实例部分，修改成XHProfRuns_MySQL：

    $xhprof_runs_impl = new XHProfRuns_MySQL();
    //$xhprof_runs_impl = new XHProfRuns_Default();


__EOF__
