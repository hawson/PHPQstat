<?php
// Script to get some info out of Zabbix
$sql = "select h.host, h.hostid from hosts  h, hosts_groups g where h.hostid=g.hostid and g.groupid=7 and h.host regexp '^sge[0-9]' order by h.host";

$db_host = "mysqlza";
$db_user = "zbx_hoster";
$db_passcode = "info4bix";
$db_name = "zabbix";

$host_ids = array();  // Array the holds the result of the query

$conn = new mysqli($db_host, $db_user, $db_passcode, $db_name );

$db_result = $conn->query($sql) or die(mysqli_error());

while ($row = $db_result->fetch_row() ) {
    $host = $row[0];
    $hostid = $row[1];

    $zbx_url ="https://zabbix.ncbi.nlm.nih.gov/misc/screens.php?form_refresh=1&elementid=18&hostid=".$hostid."&groupid=7";
   $fqdn = "$row[0]".".be-md.ncbi.nlm.nih.gov";

   $host_ids["$fqdn"] = $zbx_url;
}

//print_r($host_ids);
?>
