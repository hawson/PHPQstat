<?php
// Script to get some info out of Zabbix
$sql = "select h.host, h.hostid from hosts  h, hosts_groups g where h.hostid=g.hostid and g.groupid=7 and h.host regexp '^sge[0-9]' order by h.host";

$db_host = "mysqlza";
$db_user = "zbx_hoster";
$db_passcode = "info4bix";
$db_name = "zabbix";

$zabbix_icon = 'https://zabbix.ncbi.nlm.nih.gov/linux/images/general/zabbix.ico';

$zabbix_ids = array();  // Stores mapping from hostname (both FQDN and shorname) to Zabbix link

$conn = new mysqli($db_host, $db_user, $db_passcode, $db_name );

$db_result = $conn->query($sql) or die(mysqli_error());

while ($row = $db_result->fetch_row() ) {
    $host = $row[0];
    $hostid = $row[1];

    $zbx_url = "https://zabbix.ncbi.nlm.nih.gov/misc/screens.php?form_refresh=1&elementid=18&hostid=$hostid&groupid=7";
    $fqdn = "$row[0].be-md.ncbi.nlm.nih.gov";

    $zabbix_ids[$fqdn] = $zbx_url;
    $zabbix_ids[$row[0]] = $zbx_url;
}


# Builds returns bare URL for Zabbix host
function zabbix_url ($host) {
    global $zabbix_ids;
    return array_key_exists($host, $zabbix_ids) ? $zabbix_ids[$host] : false ;
}

# Builds HTML link for Zabbix URL
function zabbix_link($host) {
    global $zabbix_icon;
    $url = zabbix_url($host);
    if ($url) {
        $link = '<a href="' . $url ."\"><img src=\"$zabbix_icon\"></a>";
    } else {
        $link = '';
    }
    #print "URL=$url host=$host link=$link\n\n";

    return $link;
}

//print_r($zabbix_ids);
?>
