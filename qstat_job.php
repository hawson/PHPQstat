<html>

<head>
  <title>PHPQstat</title>
  <meta name="AUTHOR" content="Jordi Blasco Pallares ">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="KEYWORDS" content="gridengine sge sun hpc supercomputing batch queue linux xml qstat qhost jordi blasco solnu">
  <link rel="stylesheet" href="phpqstat.css" type="text/css" /> 
</head>

<?php
require('time_duration.php');
$owner   = isset($_GET['owner']) ? $_GET['owner'] : null;
$jobid   = isset($_GET['jobid']) ? $_GET['jobid'] : null;
$jobstat = isset($_GET['jobstat']) ? $_GET['jobstat'] : null;

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
      <td>
<br>




<?php
$password_length = 20;

function make_seed() {
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}

srand(make_seed());

$token = uniqid('phpqstat_');
$tokenfile = "/tmp/$token.xml";

if($jobstat){$jobstatflag="-s $jobstat";}else{$jobstatflag="";}
$out = exec("./gexml -j $jobid $jobstatflag -u all -o $tokenfile");

$qstat = simplexml_load_file($tokenfile);

//foreach ($qstat->xpath('detailed_job_info->djob_info->element') as $element) {
//foreach ($qstat->element[0] as $element) {
$job_name=$qstat->djob_info->element[0]->JB_job_name;
$job_owner=$qstat->djob_info->element[0]->JB_owner;
$job_group=$qstat->djob_info->element[0]->JB_group;
$job_project=$qstat->djob_info->element[0]->JB_project;
$job_ust=$qstat->djob_info->element[0]->JB_submission_time;
$job_st=date('r', date($job_ust * 1 ) );
$job_qn=$qstat->djob_info->element[0]->JB_ja_tasks->ulong_sublist->JAT_grandted_destin_identifier_list->JG_qname;
$job_pe=$qstat->djob_info->element[0]->JB_pe;
$job_slots=$qstat->djob_info->element[0]->JB_pe_range->ranges->RN_min;

echo "	<table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
		<tr CLASS=\"header\">
		<td>JobID</td>
                <td>Name</td>
                <td>Owner</td>
                <td>Group</td>
                <td>Project</td>
                <td>SubmitTime</td>
                <td>Queue</td>
                <td>PE</td>
                <td>Slots</td>
                </tr>
                <tr>
                <td>$jobid</td>
                <td>$job_name</td>
                <td>$job_owner</td>
                <td>$job_group</td>
                <td>$job_project</td>
                <td>$job_st</td>
                <td>$job_qn</td>
                <td>$job_pe</td>
                <td>$job_slots</td>
              </tr>	  
           </tbody>
	</table><br>";


$i=0;
$usage_stats = array();
foreach ($qstat->xpath('//scaled') as $usage) {
    $key=strval($usage->UA_name);
    $val=strval($usage->UA_value);
    $usage_stats[$key]=$val;
}

$cputime = ($usage_stats['cpu'] > 0) ?  $cputime = time_duration($usage_stats['cpu'], 'dhms') : 0;

echo "	<table align=center width=95% border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
		<tr CLASS=\"header\">
            <td>CPUTime (s)</td>
            <td>Mem (GB)</td>
            <td>io</td>
            <td>iow</td>
            <td>VMem (M)</td>
            <td>MaxVMem (M)</td>
        </tr>
        <tr>
            <td>$cputime</td>
            <td>".number_format($usage_stats['mem'], 2, '.', '')."</td>
            <td>".number_format($usage_stats['io'], 2, '.', '')."</td>
            <td>".number_format($usage_stats['iow'], 2, '.', '')."</td>
            <td>".number_format($usage_stats['vmem']/1024/1024, 2, '.', '')."</td>
            <td>".number_format($usage_stats['maxvmem']/1024/1024, 2, '.', '')."</td>
       </tr>	  
       </tbody>
	</table><br>";

unlink($tokenfile);
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

