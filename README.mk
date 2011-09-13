======
auto xhprof
======

通过设置php.ini中的auto_prepend_file，加载auto-xhprof，保存数据到MySQL。

常量的定义请参考auto-xhprof.php中define部分。

页面展示部分请修改index.php/callgraph.php中$xhprof_runs_impl实例部分，修改成XHProfRuns_MySQL。
  
