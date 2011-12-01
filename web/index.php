<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Auto-XHPorf List</title>
<style>
body {margin: 10px;}
td {font-size:12px;word-break: break-all;}
</style>
</head>

<body>
<?php
$db = new SimpleDB();
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
?>
<h4>Auto-XHProf List</h4>
<table width="100%" border="0" cellspacing="1" cellpadding="4" bgcolor="#AAA">
<tr bgcolor="#CCC">
<td width="5%">ID</td><td width="25%">URL</td><td width="8%">Resp Time</td><td>Error Desc</td><td width="10%">Profile</td><td width="10%">Client Time</td></tr>
<?php
$rows = $db->query("SELECT * FROM ax_log ORDER BY id DESC LIMIT 100");
if ($rows) {
    foreach ($rows as $row){
        $error_id  = $row['error_id'];
        $xhprof_id = $row['xhprof_id'];
        if (!empty($error_id)) {
            $error = $db->query("SELECT * FROM ax_error WHERE id='".$error_id."'");
            if ($error != null) {
                $error_id = "<font color='red'>".$err[$error[0]['type']].":</font> ".$error[0]['message']." in ".$error[0]['file']." on line <b>".$error[0]['line']."</b>";
            }
        }
        if (!empty($xhprof_id)) {
            $xhprof_id = "<a href='xhprof/index.php?run=".$xhprof_id."'>".$xhprof_id."</a>";
        }
        printf("<tr bgcolor='#FFF' valign='top'><td>%s</td><td>http://%s</td><td>%s s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
            $row['id'], $row['host'].$row['uri'], $row['resp_time'], $error_id, $xhprof_id, $row['client_time']);
    }
}
?>
</table>
</body>

</html>
<?php
$db->close();
?>
