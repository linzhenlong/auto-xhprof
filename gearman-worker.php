<?php
include_once 'auto-xhprof.php';

// 注册后台保存xhprof数据
function xhprof_write($job) {
    $data = unserialize($job->workload());
    $xhprof_run  = new XHProfRuns_DB();
    $run_id      = $xhprof_run->save_run($data);
    echo "gearman save id: $run_id\n";
}

// 初始化worker
$worker = new GearmanWorker();

// 添加server
foreach (explode(";", $GLOBALS['AX_GEARMAN_SERVER']) as $server) {
    list($host, $port) = explode(":", $server);
    $worker->addServer($host, $port);
}

// 注册函数
$worker->addFunction('xhprof.write', 'xhprof_write');

// run
while($worker->work());

?>
