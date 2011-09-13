======
auto xhprof
======

通过设置php.ini中的auto_prepend_file，加载auto-xhprof，保存数据到MySQL。

    ;php.ini
    auto_prepend_file = '/path/prepend.php'

    ;prepend.php
    <?php
       include_once '/path/auto-xhprof.php';
    ?>

常量的定义请参考auto-xhprof.php中define部分。

    define('__XHPROF_DIR',            dirname(__FILE__));             // 根目录
    define('__XHPROF_LIB_DIR',        __XHPROF_DIR . '/xhprof_lib/'); // xhprof_lib 目录

    define('__XHPROF_AUTO_START',     true); // 是否自动打开全局xhprof
    define('__XHPROF_SAVE_TIMEOUT',   2);    // 超过几秒自动保存数据到MySQL

    define('__XHPROF_MYSQL_HOST',     '127.0.0.1'); // MySQL 主机
    define('__XHPROF_MYSQL_USER',     'root');      // MySQL 用户名
    define('__XHPROF_MYSQL_PASS',     '123456');    // MySQL 账户密码
    define('__XHPROF_MYSQL_DB',       'xhprof');    // 表名

页面展示部分请修改index.php/callgraph.php中$xhprof_runs_impl实例部分，修改成XHProfRuns_MySQL。

    $xhprof_runs_impl = new XHProfRuns_MySQL();
    //$xhprof_runs_impl = new XHProfRuns_Default();

xhprof表结构：

    CREATE TABLE xhprof (
      run_id varchar(64) DEFAULT NULL,
      type varchar(256) DEFAULT NULL,
      data text,
      optime datetime DEFAULT NULL
    );

