ABOUT PHPQstat
==============================================
PHPQstat is a web interface that allows to connect to the useful commands
of the Sun Grid Engine (SGE) batch queue system. With this interface,
you can monitor your job status and your queues health at real time.

AUTHOR : 
* Written by Jordi Blasco Pallarès (jordi.blasco@gmail.com).
* Updates by Hawson <hawson@gmail.com>

URL: https://github.com/hawson/PHPQstat

REPORTING BUGS : Report bugs to github at the URL above

LICENSE : This is free software: you are free to change and redistribute
          it. GNU General Public License version 3.0 (GPLv3).

Version : 0.3.0 (31 July 2014)


Please, visit our forum project and send us your feedback.

DEPENDENCIES
==============================================
You will need Apache server, php5, rrdtool, awk, and perl.

INSTALL
==============================================
1. Copy all files in your web accesible filesystem or download the project using GIT:
    `git clone git://github.com/HPCKP/PHPQstat.git`
2. Setup the following paths on phpqstat.conf :
```
    SGE_ROOT=/sge
    RRD_ROOT=/var/www/PHPQstat/rrd
    WEB_ROOT=/var/www/PHPQstat`
```
3. Add the following lines on the crontab :
```
    */3 * * * * cd /var/www/PHPQstat; /var/www/PHPQstat/accounting.sh > /dev/null 2>&1
    */3 * * * * cd /var/www/PHPQstat; /var/www/PHPQstat/accounting_quota.pl  > /dev/null
    */3 * * * * cd /var/www/PHPQstat; /var/www/PHPQstat/accounting_project.pl  > /dev/null
```


SCREENSHOTS
==============================================
![Running Jobs by Queue](https://raw.githubusercontent.com/hawson/PHPQstat/master/screenshots/day.png)
![Total Pending Jobs](https://raw.githubusercontent.com/hawson/PHPQstat/master/screenshots/qw_day.png)
![Running Jobs by Resource Quotas](https://raw.githubusercontent.com/hawson/PHPQstat/master/screenshots/quota_day.png)


ROADMAP
==============================================
* 0.1 Functional
* 0.2 Real-time accounting
* 0.3 Updates to accounting metrics
* 0.4 ???

TODO LIST
==============================================
* Group joblist
* all users joblist
* Job info (submission time, wait time, walltime, cputime, efficiency=(cputime/(walltime*slots))

CHANGELOG
==============================================
* 0.3.0 Continued development, add RQS and Project accounting.
* 0.2.0 Real-time accounting feature
* 0.1.3 Solved problems with Start time and Submission Time
* 0.1.2 Solved problem on cputime request on pending job
* 0.1.1 Install instructions and job details support
* 0.1.0 Project started
