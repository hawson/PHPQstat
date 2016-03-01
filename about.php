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
<center>
<table align=center width=50% border="0" cellpadding="0" cellspacing="0">
<tr><td align=center>
<b>PHPQstat</b> is a web interface that allows to connect to the usefull commands of the Grid Engine (GE) batch queue system. With this interface, you can monitor your job status and your queues health at real time.<br><br>
<b>AUTHOR</b> Written by Jordi Blasco Pallarès, with later improvements by Lydia Sevelt and Jesse Becker<br>
<b>REPORTING BUGS</b> Report bugs to <a href="https://github.com/hawson/PHPQstat">GitHub</a>
<b>LICENSE</b> This is free software: you are free to change and redistribute it. GNU General Public License version 3.0 (<a href=http://gnu.org/licenses/gpl.html target=gpl>GPLv3</a>).<br>
<b>Version : 0.3.0 (February 2012)</b><br><br>
<a href="https://github.com/hawson/PHPQstat">https://github.com/hawson/PHPQstat</a><br>
</td></tr>
</table>
</center>
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

