<?php
include_once 'auto-xhprof.php';

// 注册后台保存xhprof数据
function xhprof_write($job) {
    $stream = unserialize($job->workload());
    $type   = $stream['type'];
    $data   = $stream['data'];
    $xhprof_run  = new XHProfRuns_MySQL();
    $run_id      = $xhprof_run->save_run($data, $type);
    echo "gearman save id: $run_id\n";
}

// 初始化worker
$worker = new GearmanWorker();

// 添加server
foreach (explode(";", __XHPROF_GERAMAN_SERVERS) as $server) {
    list($host, $port) = explode(":", $server);
    $worker->addServer($host, $port);
}

// 注册函数
$worker->addFunction('xhprof.write', 'xhprof_write');

// run
while($worker->work());

?>
