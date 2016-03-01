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
$jobstat = isset($_GET['jobstat']) ? $_GET['jobstat'] : null ;
$queue   = isset($_GET['queue']) ? $_GET['queue'] : null ;
echo "<body><table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\"><tbody>";
include("header.php");
include_once('qzbix.php');


$token = uniqid('phpqstat_');
$tokenfile="/tmp/$token.xml";

if ($qstat_reduce != "yes" ) {

	$password_length = 20;
	function make_seed() {
	  list($usec, $sec) = explode(' ', microtime());
	  return (float) $sec + ((float) $usec * 100000);
	}

	srand(make_seed());
}

function show_run($qstat,$owner,$queue) {
  global $qstat_reduce;
  global $tokenfile;

  echo "<table align=center width=95%xml border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
	  <tbody>
		  <tr>
		  <td CLASS=\"header\" width=120 colspan=10><b>Running Jobs</b></td></tr>
		  <tr>
		  <td>JobID</td>
		  <td>Owner</td>
		  <td>Priority</td>
		  <td>Name</td>
		  <td>State</td>
		  <td>Project </td>
		  <td>Queue </td>
		  <td>Start Time</td>
		  <td>PE</td>
		  <td>Slots</td>
		  </tr>";
  
  #if ($qstat_reduce != "yes" ) {
  #	$qstat = simplexml_load_file($tokenfile);
  #}

  if     ($owner == 'all') { $owner ='*'; }
  elseif ($owner)          { $owner = "'$owner'"; }
  else                     { $owner = '*'; }

  $queue = $queue ? "'$queue'" : '*';

  $xpath = "/job_info/queue_info/job_list[@state='running' and JB_owner=$owner and queue_name=$queue]";
  #print "\n\n$xpath\n\n";
  foreach ($qstat->xpath($xpath) as $job_list) {

	  $pe=$job_list->requested_pe['name'];

      $queue = $job_list->queue_name;
      $queue_display = preg_replace('/\.be-md.*$/', '', $queue);

	  list($qname, $sgehost) = split("@", $job_list->queue_name);

      // Add Zabbix Link
      $zabbix_link = zabbix_link($sgehost);
      // END of Zabbix Link

      $qstat_user_link = '<a href="' . "qstat_user.php?queue=$queue&owner=$owner" . '">' . $queue_display . '</a>';

	  echo "          <tr>
			  <td><a href=qstat_job.php?jobid=$job_list->JB_job_number&owner=$owner>$job_list->JB_job_number</a></td>
			  <td><a href=qstat_user.php?owner=$job_list->JB_owner>$job_list->JB_owner</a></td>
			  <td>$job_list->JAT_prio</td>
			  <td>$job_list->JB_name</td>
			  <td>$job_list->state</td>
			  <td>$job_list->JB_project</td>
			  <td>$qstat_user_link$zabbix_link</td>
			  <td>$job_list->JAT_start_time</td>
			  <td>$pe</td>
			  <td>$job_list->slots</td>
			  </tr>";
  }
  echo "</tbody></table><br><br>\n";

}

function show_pend($qstat,$owner,$queue) {
  global $qstat_reduce;
  global $tokenfile;
  echo "<table align=center width=95%xml border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
	  <tbody>
		  <tr>
		  <td CLASS=\"header\" width=120 colspan=10><b>Pending Jobs</b></td></tr>
		  <tr>
		  <td>JobID</td>
		  <td>Owner</td>
		  <td>Priority</td>
		  <td>Name</td>
		  <td>State</td>
		  <td>Project </td>
		  <td>Queue </td>
		  <td>Submission Time</td>
		  <td>PE</td>
		  <td>Slots</td>
		  </tr>";
  if ($qstat_reduce != "yes" ) {
  	$qstat = simplexml_load_file($tokenfile);
  }

  if     ($owner == 'all') { $owner ='*'; }
  elseif ($owner)          { $owner = "'$owner'"; }
  else                     { $owner = '*'; }

  $queue = $queue ? "'$queue'" : '*';

  # Note this is '/job_info/job_info' (repeated)!
  $xpath = "/job_info/job_info/job_list[@state='pending' and JB_owner=$owner]";
  #print "\n\n$xpath\n\n";
  foreach ($qstat->xpath($xpath) as $job_list) {

	  $pe=$job_list->requested_pe['name'];
	  echo "          <tr>
			  <td><a href=qstat_job.php?jobid=$job_list->JB_job_number&owner=$owner>$job_list->JB_job_number</a></td>
			  <td><a href=qstat_user.php?owner=$job_list->JB_owner>$job_list->JB_owner</a></td>
			  <td>$job_list->JAT_prio</td>
			  <td>$job_list->JB_name</td>
			  <td>$job_list->state</td>
			  <td>$job_list->JB_project</td>
			  <td><a href=qstat_user.php?queue=$job_list->queue_name&owner=$owner>$job_list->queue_name</a></td>
			  <td>$job_list->JB_submission_time</td>
			  <td>$pe</td>
			  <td>$job_list->slots</td>
			  </tr>";
  }
  echo "</tbody></table><br>";

}


echo "<tr><td><h1>PHPQstat</h1></td></tr>
      <tr>
      <td CLASS=\"header\" align=center><a href='index.php'>Home</a> 
      * <a href=\"qhost.php?owner=$owner\">Hosts status</a> 
      * <a href=\"qstat.php?owner=$owner\">Queue status</a> 
      * <a href=\"qstat_user.php?owner=$owner\">Jobs status ($owner)</a> 
      * <a href=\"about.php?owner=$owner\">About PHPQstat</a></td>
      </tr>
      <tr><td><br>";

if($queue){$queueflag="-q $queue";}else{$queueflag="";}

if($jobstat){$jobstatflag="-s $jobstat";}else{$jobstatflag="";}

if ($qstat_reduce == "yes" ) {
	$qstat = simplexml_load_file("/tmp/qstat_all.xml");
}

switch ($jobstat) {
    case "r":
        $jobstatflag="-s r";
        if ($qstat_reduce != "yes" ) {
            $out = exec("./gexml -u $owner $jobstatflag $queueflag -o $tokenfile");   
            show_run("",$owner,$queue);
            unlink($tokenfile);
        } else {
            show_run($qstat,$owner,$queue);
        }
        break;
    case "p":
        $jobstatflag="-s p";
        if ($qstat_reduce != "yes" ) {
	        $out = exec("./gexml -u $owner $jobstatflag $queueflag -o /tmp/$token.xml");
	        show_pend("",$owner,$queue);
            unlink($tokenfile);
        } else {
            show_pend($qstat,$owner,$queue);
        }
        break;
    default:
        $jobstatflag="-s r";
        if ($qstat_reduce != "yes" ) {
            $out = exec("./gexml -u $owner $jobstatflag $queueflag -o /tmp/$token.xml");
            show_run("",$owner,$queue);
            unlink($tokenfile);
        } else {
            show_run($qstat,$owner,$queue);
        }

        $jobstatflag="-s p";
        if ($qstat_reduce != "yes" ) {
                $out = exec("./gexml -u $owner $jobstatflag $queueflag -o /tmp/$token.xml");
                show_pend("",$owner,$queue);
                unlink($tokenfile);
        } else {
                show_pend($qstat,$owner,$queue);
        }
    break;
}

?>
	  

      </td>
    </tr>
<?php
include("bottom.php");
?>
  </tbody>
</table>



</body>
</html>

