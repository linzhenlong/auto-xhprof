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
    $query = mysql_query("SELECT * FROM xhprof ORDER BY optime DESC", $db);
?>
<h3>XHProf List</h3>
<table border="0" cellspacing="1" cellpadding="4">
<tr bgcolor="#CCC">
<td>id</td><td>url</td><td>run time</td><td>record time</td>
</tr>
<?php
    while ($row = mysql_fetch_array($query)) {
        printf("<tr><td>%s</td><td><a href='xhprof/index.php?run=%s&source=%s'>%s</a></td><td>%s ms</td><td>%s</td>\n", $row['run_id'], $row['run_id'], $row['url'], $row['url'], $row['runtime'],$row['optime']);
    }
    mysql_close();
?>
</table>
</body>

</html>
