<html>

<head>
  <title>PHPQstat</title>
  <meta name="AUTHOR" content="Jordi Blasco Pallares ">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="KEYWORDS" content="gridengine sge sun hpc supercomputing batch queue linux xml qstat qhost jordi blasco solnu">
  <link rel="stylesheet" href="phpqstat.css" type="text/css" /> 

 
</head>

<?php
$owner  = array_key_exists('owner', $_GET) ? $_GET['owner'] : 'all';
$jobstat = isset($_GET['jobstat']) ? $_GET['jobstat'] : null ;
$queue   = isset($_GET['queue']) ? $_GET['queue'] : null ;

include("header.php");
include_once('qzbix.php');

$token = uniqid('phpqstat_');
$tokenfile="/tmp/$token.xml";

function clean_owner($owner) {
    switch ($owner) {
    case 'all':
    case '*':
    case '':
    case !isset($owner):
        $cleaned = '*';
        break;
    default:
        $cleaned = $owner;
        break;
    }
    return $cleaned;
    
}

function show_run($qstat,$owner,$queue) {

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
  
  $owner = clean_owner(trim($owner));
  $queue = clean_owner(trim($queue));  //Works for both

  $xpath_query = "@state='running'";

  $xpath_query .= (empty($owner) or $owner == '*') ? '' : " and JB_owner='$owner'" ;
  $xpath_query .= (empty($queue) or $queue == '*') ? '' : " and starts-with(queue_name, '$queue@')";


  $xpath = "/job_info/queue_info/job_list[$xpath_query]";
  #print "\n\n$xpath\n\nqueue=$queue owner=$owner";
  foreach ($qstat->xpath($xpath) as $job_list) {

	  $pe=$job_list->granted_pe['name'];

      $slots = $pe ? $job_list->granted_pe : 1;

      $queue = $job_list->queue_name;
      $queue_display = preg_replace('/\.be-md.*$/', '', $queue);

	  list($qname, $sgehost) = split("@", $job_list->queue_name);

      // Add Zabbix Link
      $zabbix_link = zabbix_link($sgehost);
      // END of Zabbix Link

      $qstat_user_link = '<a href="' . "qstat_user.php?queue=$queue&owner=$owner" . '">' . $queue_display . '</a>';
      $job_number = $job_list->JB_job_number;


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
			  <td>$slots</td>
			  </tr>";
  }
  echo "</tbody></table><br><br>\n";

}

function show_pend($qstat,$owner,$queue) {

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
		  <td>Submission Time</th>
		  <td>PE</th>
		  <td>Tasks</th>
		  </tr>";

  $owner = clean_owner($owner);
  $queue = $queue ? "'$queue'" : '*';

  # Note this is '/job_info/job_info' (repeated)!
  $xpath = "/job_info/job_info/job_list[@state='pending' and JB_owner=$owner]";
  #print "\n\n$xpath\n\n";
  foreach ($qstat->xpath($xpath) as $job_list) {

	  $pe = $job_list->requested_pe['name'];
      if ($pe) { $pe .= ':' . $job_list->requested_pe; }

      $tasks = $job_list->tasks ? $job_list->tasks : 1;

	  echo "          <tr>
			  <td><a href=qstat_job.php?jobid=$job_list->JB_job_number&owner=$owner>$job_list->JB_job_number</a></td>
			  <td><a href=qstat_user.php?owner=$job_list->JB_owner>$job_list->JB_owner</a></td>
			  <td>$job_list->JAT_prio</td>
			  <td>$job_list->JB_name</td>
			  <td>$job_list->state</td>
			  <td>$job_list->JB_project</td>
			  <td>$job_list->JB_submission_time</td>
			  <td>$pe</td>
			  <td align='right'>$tasks</td>
			  </tr>";
  }
  echo "</tbody></table><br>";

}

############################################################################################################

echo "<body><table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\"><tbody>";


echo "<tr><td><h1>PHPQstat</h1></td></tr>
      <tr><td CLASS=\"header\" align=center>
      <a href=\"qstat.php?owner=$owner\">Queue status</a> &bull;
      <a href=\"qhost.php?owner=$owner\">Hosts status</a> &bull;
      <a href=\"qstat_user.php?owner=$owner\">Jobs status ($owner)</a> &bull;
      <a href=\"about.php?owner=$owner\">About PHPQstat</a>
      </td></tr>";

echo '<tr><td align="center">';

print "<form action=qstat_user.php method=get>\n
User:  <input size=10 name=owner type=text value=\"$owner\"><input type=submit value='Enter'> &bull;
Queue: <input size=10 name=queue type=text value=\"$queue\"><input type=submit value='Enter'> &bull;
Job:   <input size=10 name=job   type=text value=\"$job\"><input type=submit value='Enter'></form> ";

echo "</td></tr>";

echo "<tr><td><br>";

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

