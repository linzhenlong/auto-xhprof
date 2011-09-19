<html>
<head>
<title>XHPorf List</title>
<style>
body {margin: 10px;}
td {font-size:14px;}
</style>
</head>

<body>
<?php
    $db = mysql_connect(__XHPROF_MYSQL_HOST, __XHPROF_MYSQL_USER, __XHPROF_MYSQL_PASS);
    mysql_select_db(__XHPROF_MYSQL_DB);
    $query = mysql_query("SELECT * FROM xhprof_log ORDER BY optime DESC", $db);
?>
<h4>XHProf List</h4>
<table border="0" cellspacing="1" cellpadding="4">
<tr bgcolor="#CCC">
<td>id</td><td>url</td><td>run time</td><td>record time</td>
</tr>
<?php
    while ($row = mysql_fetch_array($query)) {
        printf("<tr><td>%s</td><td><a href='xhprof/index.php?run=%s&source=%s'>%s</a></td><td>%s ms</td><td>%s</td>\n", 
            $row['run_id'], $row['run_id'], $row['url'], $row['url'], $row['runtime'],$row['optime']);
    }
?>
</table>

<?php
    $query = mysql_query("SELECT * FROM xhprof_error ORDER BY id DESC");
?>
<h4>Error List</h4>
<table border="0" cellspacing="1" cellpadding="4">
<tr bgcolor="#CCC">
<td>id</td><td>type</td><td>message</td><td>file [num]</td><td>record time</td>
</tr>
<?php
    $err = array(
        1     => 'E_ERROR',
        2     => 'E_WARNING',
        4     => 'E_PARSE',
        8     => 'E_NOTICE',
        16    => 'E_CORE_ERROR',
        32    => 'E_CORE_WARNING',
        64    => 'E_COMPILE_ERROR',
        128   => 'E_COMPILE_WARNING',
        256   => 'E_USER_ERROR',
        512   => 'E_USER_WARNING',
        1024  => 'E_USER_NOTICE',
        2048  => 'E_STRICT',
        4096  => 'E_RECOVERABLE_ERROR',
        8192  => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
        32767 => 'E_ALL'
    );
    while ($row = mysql_fetch_array($query)) {
        printf("<tr><td>%s</td><td><b>%s</b></td><td>%s</td><td>%s [%s]</td><td>%s</td>\n", 
            $row['id'], $err[$row['type']], $row['message'], $row['file'], $row['line'],$row['optime']);
    }
?>
</table>
</body>

<?php mysql_close(); ?>
</html>
