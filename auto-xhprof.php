<?php
define('__XHPROF_DIR',     dirname(__FILE__));
define('__XHPROF_LIB_DIR', __XHPROF_DIR . '/xhprof_lib/');

include_once __XHPROF_LIB_DIR . '/utils/xhprof_lib.php';
include_once __XHPROF_LIB_DIR . '/utils/xhprof_runs.php';

function getmicrotime() { // 获取毫秒
    list($usec, $sec) = explode(' ',microtime());  
    return ((float)$usec + (float)$sec);  
}

$GLOBALS['AX_PAGE_START_TIME'] = getmicrotime();  // 页面启动时间
$GLOBALS['AX_XHPROF_IS_RUN']   = true;           // 当前页面是否启动xhprof

// 获取ini中相关配置信息
$ax_ini    = parse_ini_file(__XHPROF_DIR . '/auto-xhprof.ini', true);
$ax_config = $ax_ini['auto_xhprof'];
$GLOBALS['AX_XHPROF_AUTOSTART'] = get_ini_value($ax_config, 'xhprof.autostart');
$GLOBALS['AX_XHPROF_TIMEOUT']   = get_ini_value($ax_config, 'xhprof.timeout');
$GLOBALS['AX_XHPROF_IGNORE']    = get_ini_value($ax_config, 'xhprof.ignore');
$GLOBALS['AX_DATABASE_DSN']     = get_ini_value($ax_config, 'database.dsn');
$GLOBALS['AX_DATABASE_USER']    = get_ini_value($ax_config, 'database.user');
$GLOBALS['AX_DATABASE_PASS']    = get_ini_value($ax_config, 'database.pass');
$GLOBALS['AX_GEARMAN_SERVER']   = get_ini_value($ax_config, 'gearman.server');

$xhprof_enabled  = module_enabled('xhprof');  // 检查xhprof模块是否可用
$gearman_enabled = module_enabled('gearman'); // 检查gearman模块是否可用
if ($gearman_enabled && !$GLOBALS['AX_GEARMAN_SERVER']) {
    $gearman_enabled = false;
}

function get_ini_value($ini, $key) {
    return array_key_exists($key, $ini) ? $ini[$key] : false;
}

function module_enabled($module) { // 检查模块是否可用
    return in_array($module, get_loaded_extensions());
}

function xhprof_start() { // 打开xhprof
    global $xhprof_enabled;
    if ($xhprof_enabled) {
        xhprof_enable();
        $GLOBALS['AX_XHPROF_IS_RUN']= true;
    }
}

function xhprof_stop() { // 关闭xhprof
    global $gearman_enabled;
    $error       = error_get_last();
    $resp_time   = sprintf("%.4f", $GLOBALS['AX_PAGE_END_TIME'] - $GLOBALS['AX_PAGE_START_TIME']);
    if (!empty($error) || $GLOBALS['AX_XHPROF_IS_RUN'] || $resp_time >= $GLOBALS['AX_XHPROF_TIMEOUT']) {
        $data = array();
        $data['host']        = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $data['uri']         = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
        $data['client_time'] = date('Y-m-d H:i:s');
        $data['resp_time']   = $resp_time;
        $data['xhprof']      = null;
        if ($GLOBALS['AX_XHPROF_IS_RUN']) {
            $data['xhprof'] = xhprof_disable();
            $GLOBALS['AX_XHPROF_IS_RUN'] = false;
        }
        $data['error']     = $error;
        // 检查是否安装gearman扩展，并已设置gearman server
        /**
        if ($gearman_enabled) {
            $ret = do_background_job('xhprof.write', serialize($data));
            echo "\n<!-- gearman save: $ret -->\n";
        } else {
            $xhprof_run = new XHProfRuns_DB();
            $run_id     = $xhprof_run->save_run($data);
            echo "\n<!-- xhprof save, id: $run_id -->\n";
        }
         * */
        $xhprof_run = new XHProfRuns_DB();
        $run_id     = $xhprof_run->save_run($data);
        //echo "\n<!-- xhprof save, id: $run_id -->\n";
    }
}

function do_background_job($action, $data) { // 保存数据到gearman，异步执行
    $gm = new GearmanClient();
    foreach (explode(";", $GLOBALS['AX_GEARMAN_SERVER']) as $server) {
        list($host, $port) = explode(":", $server);
        $gm->addServer($host, $port);
    }
    @$gm->doBackground($action, $data);
    if ($gm->returnCode() == GEARMAN_SUCCESS) {
        return true;
    }
    return false;
}

function default_shutdown_handler() { // 默认shutdown处理函数
    // 匹配不需要记录的关键字
    foreach (explode(";", $GLOBALS['AX_XHPROF_IGNORE']) as $r) {
        if (strpos($_SERVER['PHP_SELF'], $r) !== false) {
            return false;
        }
    }
    // phpmyadmin获取不到$GLOBALS值
    if (empty($GLOBALS['AX_PAGE_START_TIME'])) {
        return false;
    }
    $GLOBALS['AX_PAGE_END_TIME'] = getmicrotime();
    //printf("\n<!-- page run time: %.3f ms, xhprof: %d -->\n", $GLOBALS['AX_PAGE_END_TIME']-$GLOBALS['AX_PAGE_START_TIME'], $GLOBALS['AX_XHPROF_IS_RUN']);
    // 停止xhprof
    xhprof_stop();
}


class SimpleDB {

    private $db = null;

    public function __construct() {
        try {
            $this->db = new PDO($GLOBALS['AX_DATABASE_DSN'], $GLOBALS['AX_DATABASE_USER'], $GLOBALS['AX_DATABASE_PASS']);
        } catch (PDOException $e) {
            $this->db = null;
        }
    }

    public function query($sql) {
        $rows = $this->db->query($sql);
        if (empty($rows)) {
            return null;
        }
        return $rows->fetchAll();
    }

    public function execute($sql) {
        return $this->db->exec($sql);
    }

    public function quote($str) {
        return $this->db->quote($str);
    }

    public function close() {
        $this->db = null;
    }
}

class XHProfRuns_DB implements iXHProfRuns {

    private $db = null;

    public function __construct() {
        $this->db = new SimpleDB();
    }

    public function get_run($run_id, $type, &$run_desc) {
        if ($this->db != null) {
            $sql   = "SELECT data FROM ax_xhprof WHERE id='$run_id'";
            $rows  = $this->db->query($sql);
            if ($rows == null) {
                die("[ERROR] invalid run id: $run_id, record not found.");
            }
            $run_desc = "XHProf Run (Namespace=$type)";

            return unserialize(preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $rows[0]['data'] ));
        }
        die("[ERROR] invalid run id: $run_id, MySQL connect error.");
    }

    public function save_run($data, $type = null, $run_id = null) {
        if ($this->db != null) {
            $run_id    = uniqid();
            $error_id  = '';
            $xhprof_id = '';
            $host         = $data['host'];
            $uri          = $data['uri'];
            $resp_time    = $data['resp_time'];
            $client_time  = $data['client_time'];
            if (!empty($data['error'])) {
                $error_id = $run_id;
                $error = $data['error'];
                $sql      = sprintf("INSERT INTO ax_error(id, type, message, file, line) VALUES ('%s', %d, %s, '%s', %d)",
                    $run_id, $error['type'], $this->db->quote($error['message']), $error['file'], $error['line']);
                $this->db->execute($sql);

            }
            if (!empty($data['xhprof'])) {
                $xhprof_id = $run_id;
                $type      = $host . $uri;
                $xhprof    = $data['xhprof'];
                $xhprof = str_replace("'","\"" ,(serialize($xhprof)));
                $sql = sprintf("INSERT INTO ax_xhprof(id, type, data) VALUES ('%s', '%s', '%s')", $run_id, $type, $xhprof);
               $this->db->execute($sql);
            }
            $sql = sprintf("INSERT INTO ax_log(host, uri, resp_time, error_id, xhprof_id, client_time, log_time) 
                VALUES('%s', '%s', %s, '%s', '%s', '%s', NOW())", $host, $uri, $resp_time, $error_id, $xhprof_id, $client_time);
            $this->db->execute($sql);
            return $run_id;
        }
        die("[ERROR] invalid run id: $run_id, MySQL connect error.");
    }
}

// 加载默认处理函数
register_shutdown_function('default_shutdown_handler');

// 定义全局auto，执行start
if ($GLOBALS['AX_XHPROF_AUTOSTART'] == 1) {
    xhprof_start();
}
?>
