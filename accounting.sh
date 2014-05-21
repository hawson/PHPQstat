#!/bin/bash
#set -xv
# Exporting Environment Variables
#########################################
source ./phpqstat.conf
#########################################

if ! [ -d $RRD_ROOT ]; then mkdir -p $RRD_ROOT; fi
#QUEUES=$(qconf -sql | cut -d. -f1)


# Defines for building rrd files.
RRA=' RRA:AVERAGE:0.5:1:960 '  # 2 days, primary data points
RRA+='RRA:AVERAGE:0.2:5:2976    RRA:MAX:0.2:5:2976    RRA:MIN:0.2:5:2976 '    # 31 days, 15 min bins
RRA+='RRA:AVERAGE:0.2:20:8784   RRA:MAX:0.2:20:8784   RRA:MIN:0.2:20:8784 '   # 1 year, hourly bins
RRA+='RRA:AVERAGE:0.1:480:1830  RRA:MAX:0.2:480:1830  RRA:MIN:0.2:480:1830 '  # 5 years, daily bins

# Inici BBDD
#################
# the 'qw' rrd file can be considered a "queue" for this purpose
for q in $QUEUES "qw" ; do
    if ! [ -f $RRD_ROOT/qacct_${q}.rrd ] ; then 
        creabbdd="DS:${q}-used:GAUGE:1000000:0:999995000 "
        rrdtool create $RRD_ROOT/qacct_${q}.rrd -b -6y -s 180 $creabbdd $RRA
    fi
done


# Actualitzo la BBDD
######################
i=0 


# Queue counts
QSTAT_SUMMARY=/tmp/queue_output
qstat -g c > $QSTAT_SUMMARY

for q in $QUEUES; do
    # NOTE <---------------------------------------------------------------------
    # If your Queues don't have the .q extension, you can comment the follow line
    qname="${q}${QEXT}"
    data="N"
    cpusused=$(gawk "/^$qname /{print \$3}" $QSTAT_SUMMARY)
    cpuslimit=${CLIMIT[${i}]}
    if [ -z $cputime ] ; then cputime=0; fi
    if [ -z $cpusused ] ; then cpusused=0; fi
    data="$data:$cpusused"
    rrdupdate $RRD_ROOT/qacct_${q}.rrd $data
#    echo "rrdupdate $RRD_ROOT/qacct_${q}.rrd $data"
    i=$((i+1))
done

rm -f $QSTAT_SUMMARY

# Queue Waiting
data="N"
cpusqw=$(qstat -u '*' -s p | wc -l)
if [[ $cpusqs -gt 2 ]]; then 
    cpusqw=$(($cpusqs-2))  # to remote the header lines.
fi
data="$data:$cpusqw"
rrdupdate $RRD_ROOT/qacct_qw.rrd $data
echo "rrdupdate $RRD_ROOT/qacct_qw.rrd $data"


# Creo la grafica
######################
DATE=$(date '+%a %b %-d %H\:%M\:%S %Z %Y')

unset datagrups
for q in $QUEUES; do
 qfmt=`printf "%-16s" "$q:"`
 datagrups="$datagrups DEF:${q}-used=$RRD_ROOT/qacct_${q}.rrd:${q}-used:AVERAGE  "
 STATS="VDEF:${q}-min=${q}-used,MINIMUM  VDEF:${q}-max=${q}-used,MAXIMUM  VDEF:${q}-avg=${q}-used,AVERAGE  VDEF:${q}-last=${q}-used,LAST"
 datagrups="$datagrups $STATS"
done

i=0 
for q in $QUEUES; do
    if [[ $STACKED ]]; then
        datagrups="$datagrups AREA:${q}-used#${COLOR[${i}]}:$q:STACK"
    else
        datagrups="$datagrups LINE1:${q}-used#${COLOR[${i}]}:$q"
    fi
    pad=$((20-${#q}))
    datagrups="$datagrups GPRINT:${q}-min:%${pad}.0lf   GPRINT:${q}-avg:%5.0lf    GPRINT:${q}-max:%5.0lf     GPRINT:${q}-last:%5.0lf\\l"

    i=$((i+1))
done

# Queue Waiting
SHOWPENDING=1
if [[ -n "$SHOWPENDING" ]]; then
    pending_datagrups_LINE="DEF:slots-qw=$RRD_ROOT/qacct_qw.rrd:qw-used:AVERAGE LINE2:slots-qw#F00:slots-qw"
    pending_extrema="DEF:slots-qw-max=$RRD_ROOT/qacct_qw.rrd:qw-used:MAX DEF:slots-qw-min=$RRD_ROOT/qacct_qw.rrd:qw-used:MIN"
    pending_status="GPRINT:slots-qw:MIN:%12.0lf%s "
    pending_status+="GPRINT:slots-qw:MAX:%4.0lf%s "
    pending_status+="GPRINT:slots-qw:AVERAGE:%4.0lf%s "
    pending_status+="GPRINT:slots-qw:LAST:%4.0lf%s\\l"
fi



rrdtool graph $WEB_ROOT/img/hour.png  -a PNG -s -1hour  -t "Running Jobs (hourly)"  -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/day.png   -a PNG -s -1day   -t "Running Jobs (daily)"   -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/week.png  -a PNG -s -1week  -t "Running Jobs (Weekly)"  -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/2week.png -a PNG -s -2week  -t "Running Jobs (Weekly)"  -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/month.png -a PNG -s -1month -t "Running Jobs (Monthly)" -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/year.png  -a PNG -s -1year  -t "Running Jobs (Yearly)"  -h 200 -w 600 -v "Used CPUs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 

rrdtool graph $WEB_ROOT/img/qw_hour.png  -a PNG -s -1hour  -t "Pending Jobs (hourly)"  -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/qw_day.png   -a PNG -s -1day   -t "Pending Jobs (daily)"   -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/qw_week.png  -a PNG -s -1week  -t "Pending Jobs (Weekly)"  -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/qw_2week.png -a PNG -s -2week  -t "Pending Jobs (Weekly)"  -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/qw_month.png -a PNG -s -1month -t "Pending Jobs (Monthly)" -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/qw_year.png  -a PNG -s -1year  -t "Pending Jobs (Yearly)"  -h 200 -w 600 -v "Queued jobs" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $pending_datagrups_LINE $pending_status  COMMENT:"Last update\: $DATE" 

