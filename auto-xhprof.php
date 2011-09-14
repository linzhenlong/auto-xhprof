<?php

define('__XHPROF_DIR',            dirname(__FILE__));             // 根目录
define('__XHPROF_LIB_DIR',        __XHPROF_DIR . '/xhprof_lib/'); // xhprof_lib 目录

define('__XHPROF_AUTO_START',     true); // 是否自动打开全局xhprof
define('__XHPROF_SAVE_TIMEOUT',   2);    // 超过几秒自动保存数据到MySQL

define('__XHPROF_MYSQL_HOST',     '127.0.0.1'); // MySQL 主机
define('__XHPROF_MYSQL_USER',     'root');      // MySQL 用户名
define('__XHPROF_MYSQL_PASS',     '123456');    // MySQL 账户密码
define('__XHPROF_MYSQL_DB',       'xhprof');    // 表名

define('__XHPROF_GERAMAN_SERVERS', '127.0.0.1:4730;127.0.0.1:4730'); // gearman 服务器定义

include_once __XHPROF_LIB_DIR . 'utils/xhprof_lib.php';
include_once __XHPROF_LIB_DIR . 'utils/xhprof_runs.php';

$page_start_time = getmicrotime();            // 页面启动时间
$xhprof_enabled  = module_enabled('xhprof');  // 检查xhprof模块是否可用
$gearman_enabled = module_enabled('gearman'); // 检查gearman模块是否可用
$xhprof_running  = false;                     // 当前页面是否启动xhprof

function getmicrotime() { // 获取毫秒
    list($usec, $sec) = explode(' ',microtime());  
    return ((float)$usec + (float)$sec);  
}

function module_enabled($module) { // 检查模块是否可用
    return in_array($module, get_loaded_extensions());
}

function xhprof_start() { // 打开xhprof
    global $xhprof_enabled, $xhprof_running;
    if ($xhprof_enabled) {
        xhprof_enable();
        $xhprof_running = true;
        return true;
    }
    return false;
}

function xhprof_stop() { // 关闭xhprof
    global $xhprof_running, $gearman_enabled;
    if ($xhprof_running) {
        $xhprof_type = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $xhprof_data = xhprof_disable();
        // 检查是否安装gearman扩展，并设置gearman server
        if ($gearman_enabled && defined('__XHPROF_GERAMAN_SERVERS') && strlen(__XHPROF_GERAMAN_SERVERS) > 0) {
            $ret = do_background_job('xhprof.write',
                serialize(array('type' => $xhprof_type, 'data' => $xhprof_data)));
            echo "\n<!-- xhprof gearman save: $ret -->\n";
        } else {
            $xhprof_run  = new XHProfRuns_MySQL();
            $run_id      = $xhprof_run->save_run($xhprof_data, $xhprof_type);
            echo "\n<!-- xhprof save, id: $run_id -->\n";
        }
    }
    return false;
}

function do_background_job($action, $data) { // 保存数据到gearman，异步执行
    $gm = new GearmanClient();
    foreach (explode(";", __XHPROF_GERAMAN_SERVERS) as $server) {
        list($host, $port) = explode(":", $server);
        $gm->addServer($host, $port);
    }
    $gm->doBackground($action, $data);
    if ($gm->returnCode() == GEARMAN_SUCCESS) {
        return true;
    }
    return false;
}

function default_shutdown_handler() { // 默认shutdown处理函数，计算出页面执行时间，xhprof打开，超过定义的秒数记录到MySQL
    global $page_start_time, $xhprof_running;
    $page_run_time = (getmicrotime() - $page_start_time) * 1000;
    if ($xhprof_running && $page_run_time >= __XHPROF_SAVE_TIMEOUT * 1000) {
        xhprof_stop();
    }
    printf("\n<!-- page runtime: %.3f ms, xhprof autorun: %d -->\n", $page_run_time, $xhprof_running);
}

/* Class: XHProfRuns_MySQL
 *   保存数据到MySQL，覆盖自带的Default保存方式。
 */
class XHProfRuns_MySQL implements iXHProfRuns {

    private $db = null;

    public function __construct() {
        $db = mysql_connect(__XHPROF_MYSQL_HOST, __XHPROF_MYSQL_USER, __XHPROF_MYSQL_PASS);
        if ($db) {
            mysql_select_db(__XHPROF_MYSQL_DB);
            $this->db = $db;
        }
    }

    public function get_run($run_id, $type, &$run_desc) {
        if ($this->db != null) {
            $sql   = "SELECT data FROM xhprof WHERE run_id='$run_id'";
            $query = mysql_query($sql);
            $rows  = mysql_fetch_array($query, MYSQL_NUM);
            mysql_close();
            if ($rows == null || !is_array($rows) || count($rows) == 0) {
                die("[ERROR] invalid run id: $run_id, record not found.");
            }
            $run_desc = "XHProf Run (Namespace=$type)";
            return unserialize($rows[0]);
        }
        die("[ERROR] invalid run id: $run_id, MySQL connect error.");
    }

    public function save_run($xhprof_data, $type, $run_id = null) {
        if ($this->db != null) {
            $run_id = uniqid();
            $xhprof_data = serialize($xhprof_data);
            $sql = "INSERT INTO xhprof (run_id, type, data, optime) VALUES ('$run_id', '$type', '$xhprof_data', now())";
            $query = mysql_query($sql);
            mysql_close();
            return $run_id;
        }
        die("[ERROR] invalid run id: $run_id, MySQL connect error.");
    }
}

// 加载默认处理函数
register_shutdown_function('default_shutdown_handler');

// 定义全局auto，执行start
if (defined('__XHPROF_AUTO_START') && __XHPROF_AUTO_START) {
    xhprof_start();
}
?>
