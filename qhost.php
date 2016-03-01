<html>

<head>
  <title>PHPQstat</title>
  <meta name="AUTHOR" content="Jordi Blasco Pallares ">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="KEYWORDS" content="gridengine sge sun hpc supercomputing batch queue linux xml qstat qhost jordi blasco solnu">
  <link rel="stylesheet" href="phpqstat.css" type="text/css" /> 
</head>

<?php
$owner  = $_GET['owner'];
echo "<body><table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\"><tbody>";
include("header.php");
echo "<tr><td><h1>PHPQstat</h1></td></tr>
      <tr><td CLASS=\"header\" align=center>
      <a href=\"qstat.php?owner=$owner\">Queue status</a> * 
      <a href=\"qhost.php?owner=$owner\">Hosts status</a> *  
      <a href=\"qstat_user.php?owner=$owner\">Jobs status ($owner)</a> * 
      <a href=\"about.php?owner=$owner\">About PHPQstat</a>
      </td></tr>";
?>
    <tr>
      <td>
        <br>

	<table align=center width=95% border="1" cellpadding="2" cellspacing="0">
        <tbody>
		<tr CLASS="header">
		<th>Hostname</td>
                <th>Architecture</th>
                <th>NCPU</th>
                <th>Load avg</th>
                <th>NP Load avg</th>
                <th>mem_total</th>
                <th>mem_used</th>
                <th>swap_total</th>
                <th>swap_used</th>
                </tr>
<?php

if ($qstat_reduce != "yes") {

	$password_length = 20;

	function make_seed() {
	  list($usec, $sec) = explode(' ', microtime());
	  return (float) $sec + ((float) $usec * 100000);
	}

	srand(make_seed());

    $token=uniqid('phpqstat_');

	$out = exec("./qhostout /tmp/$token.xml");

	//printf("System Output: $out\n"); 
	$qhost = simplexml_load_file("/tmp/$token.xml");
} else {
	$qhost = simplexml_load_file("/tmp/qhost.xml");
}


#<host name='sge998.be-md.ncbi.nlm.nih.gov'>
#0 <hostvalue name='arch_string'>lx-amd64</hostvalue>
#1 <hostvalue name='num_proc'>32</hostvalue>
#2 <hostvalue name='m_socket'>2</hostvalue>
#3 <hostvalue name='m_core'>16</hostvalue>
#4 <hostvalue name='m_thread'>32</hostvalue>
#5 <hostvalue name='np_load_avg'>0.01</hostvalue>
#6 <hostvalue name='mem_total'>125.9G</hostvalue>
#7 <hostvalue name='mem_used'>3.1G</hostvalue>
#8 <hostvalue name='swap_total'>56.0G</hostvalue>
#9 <hostvalue name='swap_used'>101.6M</hostvalue>
$metrics = array(0,1,5,6,7,8,9);


// Add zabbix host URL info
include_once("qzbix.php");

$i=0;
foreach ($qhost->host as $host) {
	echo "<tr align='right'>";
    #echo "<!-- "; print_r($host); echo " -->";
	$hostname=trim($host['name']);

    //  Add zabbix link
    $zabbix_link = zabbix_link($hostname);

    echo "\t<td>$hostname$zabbix_link</td>";

	foreach ($metrics as $key) {
        $hostvalue = $host->hostvalue[$key];
        if ($key == 5) {
            $raw_load = sprintf('%.2f', floatval(floatval($hostvalue) * floatval($host->hostvalue[1])));
		    echo "	<td>$raw_load</td>";
        }
		echo "	<td>$hostvalue</td>";
	}
	echo "</tr>\n";
	$i++;
}

if ($qstat_reduce != "yes") {
    unlink("/tmp/$token.xml");
}

?>

	  </tbody>
	</table>

<br>

      </td>
    </tr>
<?php
include("bottom.php");
?>
  </tbody>
</table>



</body>
</html>

