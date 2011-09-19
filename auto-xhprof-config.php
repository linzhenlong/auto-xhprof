<?php

define('__XHPROF_DIR',            dirname(__FILE__));             // 根目录
define('__XHPROF_LIB_DIR',        __XHPROF_DIR . '/xhprof_lib/'); // xhprof_lib 目录

define('__XHPROF_AUTO_START',     true); // 是否自动打开全局xhprof
define('__XHPROF_SAVE_TIMEOUT',   2);    // 超过几秒自动保存数据到MySQL

define('__XHPROF_MYSQL_HOST',     '127.0.0.1'); // MySQL 主机
define('__XHPROF_MYSQL_USER',     'root');      // MySQL 用户名
define('__XHPROF_MYSQL_PASS',     '123456');    // MySQL 账户密码
define('__XHPROF_MYSQL_DB',       'xhprof');    // 表名

// define('__XHPROF_GERAMAN_SERVERS', '127.0.0.1:4730;127.0.0.1:4730'); // gearman 服务器定义

// 建表语句
$GLOBALS['XHPROF_LOG_SQL'] = <<<SQL
CREATE TABLE IF NOT EXISTS `xhprof_log` (
    `run_id` VARCHAR(64) PRIMARY KEY ,
    `url` VARCHAR(256) DEFAULT NULL,
    `runtime` VARCHAR(64) DEFAULT NULL,
    `data` TEXT,
    `optime` DATETIME DEFAULT NULL
);
SQL;

$GLOBALS['XHPROF_ERROR_SQL'] = <<<SQL
CREATE TABLE IF NOT EXISTS `xhprof_error` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `type` INT NOT NULL ,
    `message` VARCHAR( 512 ) NOT NULL ,
    `file` VARCHAR( 512 ) NOT NULL ,
    `line` INT NOT NULL,
    `optime` DATETIME DEFAULT NULL
);
SQL;

?>
