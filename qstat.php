<html>

<head>
  <title>PHPQstat</title>
  <meta name="AUTHOR" content="Jordi Blasco Pallares ">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="KEYWORDS" content="gridengine sge sun hpc supercomputing batch queue linux xml qstat qhost jordi blasco solnu">
  <link rel="stylesheet" href="phpqstat.css" type="text/css" /> 
<script type="text/javascript">
  function changeIt(view,target){document.getElementById(target).src= view;}
</script>
</head>
<body>

<?php
$owner  = array_key_exists('owner', $_GET) ? $_GET['owner'] : 'all';
echo "<body><table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\"><tbody>";
include("header.php");
echo "<tr><td><h1>PHPQstat</h1></td></tr>
      <tr><td CLASS=\"header\" align=center>
      <a href=\"qstat.php?owner=$owner\">Queue status</a> &bull;
      <a href=\"qhost.php?owner=$owner\">Hosts status</a> &bull;
      <a href=\"qstat_user.php?owner=$owner\">Jobs status ($owner)</a> &bull;
      <a href=\"about.php?owner=$owner\">About PHPQstat</a>
      </td></tr>";
?>
    <tr>
      <td>
<br>

	<table align=center width=95% border="1" cellpadding="0" cellspacing="0">
        <tbody>
		<tr CLASS="header">
		<td>Queue</td>
                <td>Load</td>
                <td>Used</td>
                <td>Resv</td>
                <td>Available</td>
                <td>Total</td>
                <td>Temp. disabled</td>
                <td>Manual intervention</td>
                </tr>

<?php
if ($qstat_reduce != "yes" ) {
	$password_length = 20;

	function make_seed() {
	  list($usec, $sec) = explode(' ', microtime());
	  return (float) $sec + ((float) $usec * 100000);
	}

	srand(make_seed());

    $token=uniqid('phpqstat_');

	$out = exec("./gexml -u all -R -o /tmp/$token.xml");

	//printf("System Output: $out\n"); 
	$qstat = simplexml_load_file("/tmp/$token.xml");

	//$qstat = simplexml_load_file("/home/xadmin/phpqstat/qstat_user.xml");
} else {
	$qstat = simplexml_load_file("/tmp/qstat_queues.xml");
}

foreach ($qstat->xpath('/job_info/cluster_queue_summary') as $cluster_queue_summary) {
echo "                <tr>
                <td><a href=qstat_user.php?owner=$owner&queue=$cluster_queue_summary->name>$cluster_queue_summary->name</a></td>
                <td>$cluster_queue_summary->load</td>
                <td>$cluster_queue_summary->used</td>
                <td>$cluster_queue_summary->resv</td>
                <td>$cluster_queue_summary->available</td>
                <td>$cluster_queue_summary->total</td>
                <td>$cluster_queue_summary->temp_disabled</td>
                <td>$cluster_queue_summary->manual_intervention</td>
                </tr>";
}
if ($qstat_reduce != "yes" ) {
	unlink("/tmp/$token.xml");
}

echo "                </tbody>
	</table>

<br>
	<table align=center width=95% border='1' cellpadding='0' cellspacing='0'>
        <tbody>
		<tr CLASS='header'>
		<td>Jobs status</td>
                <td>Total</td>
                <td>Slots</td>
                </tr>

";

if ($qstat_reduce != "yes" ) {
	$out2 = exec("./gexml -u all -o /tmp/$token.xml");
	$jobs = simplexml_load_file("/tmp/$token.xml");
} else {
	$jobs = simplexml_load_file("/tmp/qstat_all.xml");
}
$nrun=0;
$srun=0;
$npen=0;
$spen=0;
$nzom=0;
$szom=0;

foreach ($jobs->xpath('//job_list') as $job_list) {
    $jobstatus=$job_list['state'];

	if ($jobstatus == "running"){
		$nrun++;
		$srun=$srun+$job_list->slots;
	}
	elseif ($jobstatus == "pending"){
		$npen++;
		$spen=$spen+$job_list->slots;
	}
	elseif ($jobstatus == "zombie"){
		$nzom++;
		$szom=$szom+$job_list->slots;
	}
}
echo "          <tr>
                <td><a href=qstat_user.php?jobstat=r&owner=$owner>running</a></td>
                <td>$nrun</td>
                <td>$srun</td>
                </tr>
                <tr>
                <td><a href=qstat_user.php?jobstat=p&owner=$owner>pending</a></td>
                <td>$npen</td>
                <td>$spen</td>
                </tr>
                <tr>
                <td><a href=qstat_user.php?jobstat=z&owner=$owner>zombie</a></td>
                <td>$nzom</td>
                <td>$szom</td>
                </tr>
";
if ($qstat_reduce != "yes" ) {
	unlink("/tmp/$token.xml");
}
?>

	  </tbody>
	</table>


<?php
$mapping = array(
    'rta'       => '',
    'sm_rta'    => 'sm_',
    'qw_rta'    => 'qw_',
    'quota_rta' => 'quota_',
    'prj_rta'   => 'prj_',
);

$descr = array(
    'rta'       => 'Running Jobs',
    'sm_rta'    => 'Running Jobs (low counts)',
    'qw_rta'    => 'Pending Jobs',
    'quota_rta' => 'jobs by Quota',
    'prj_rta'   => 'jobs by Project',
);

$times = array(
    'hour',
    'day',
    'week',
    '2week',
    'month',
    'year',
);


foreach (array_keys($mapping) as $key) {
    echo '<br>
	<table align=center border="1" cellpadding="0" cellspacing="0">
        <tbody>';
    echo '<tr class="header"><td align="center">Real-time Accounting of ' . $descr[$key] . ":\n";
    foreach ($times as $time) {
        print '            <a href="#" onclick="';
        foreach (array_keys($mapping) as $type) {
            printf("changeIt('img/%s%s.png','%s');", $mapping[$type], $time, $type);
        }
        printf ("\">%s</a> %s\n", $time, $time === 'year' ? '' : '-' );
    }
    echo "    </td></tr>
        <tr><td> <img src=\"img/$mapping[$key]day.png\" id='$key' border='0'> </td></tr>
    </tbody>
    </table>";
}


?>

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

