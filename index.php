<?php
$db = mysql_connect(__XHPROF_MYSQL_HOST, __XHPROF_MYSQL_USER, __XHPROF_MYSQL_PASS);
mysql_select_db(__XHPROF_MYSQL_DB);
$query = mysql_query("SELECT * FROM xhprof ORDER BY optime DESC", $db);
?>
<h3>XHProf List</h3>
<?php
while ($row = mysql_fetch_array($query)) {
    printf("<li><a href='xhprof/index.php?run=%s&source=%s'>%s</a> %s</li>\n", $row['run_id'], $row['type'], $row['run_id'], $row['optime']);
}
mysql_close();
?>
