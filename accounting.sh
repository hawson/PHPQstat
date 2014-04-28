#!/bin/bash
#set -xv
# Exporting Environment Variables
#########################################
source ./phpqstat.conf
#########################################

if ! [ -d $RRD_ROOT ]; then mkdir -p $RRD_ROOT; fi
#QUEUES=$(qconf -sql | cut -d. -f1)

# Inici BBDD
#################
for q in $QUEUES; do
creabbdd=""
   if ! [ -f $RRD_ROOT/qacct_${q}.rrd ] ; then 
       creabbdd="${creabbdd}DS:${q}-used:GAUGE:1000000:0:999995000 "
       rrdtool create $RRD_ROOT/qacct_${q}.rrd -s 180 $creabbdd RRA:AVERAGE:0.5:1:576
   fi
done
# Queue Waiting
creabbdd="DS:slots-qw:GAUGE:1000000:0:999995000 "
[[ -f $RRD_ROOT/qacct_qw.rrd ]] || rrdtool create $RRD_ROOT/qacct_qw.rrd -s 180 $creabbdd RRA:AVERAGE:0.5:1:576


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
cpusqw=$(qstat -u * -s p | wc -l)
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
 datagrups="$datagrups DEF:slots-qw=$RRD_ROOT/qacct_qw.rrd:slots-qw:AVERAGE LINE1:slots-qw#${COLOR[${i}]}:slots-qw"
 datagrups="$datagrups GPRINT:slots-qw:MIN:%12.0lf%s"
 datagrups="$datagrups GPRINT:slots-qw:MAX:%4.0lf%s"
 datagrups="$datagrups GPRINT:slots-qw:AVERAGE:%4.0lf%s"
 datagrups="$datagrups GPRINT:slots-qw:LAST:%4.0lf%s\\l"




rrdtool graph $WEB_ROOT/img/hour.png  -a PNG -s -1hour  -t "HPC Accounting (hourly)"  -h 200 -w 600 -v "Used CPU's" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/day.png   -a PNG -s -1day   -t "HPC Accounting (daily)"   -h 200 -w 600 -v "Used CPU's" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/week.png  -a PNG -s -1week  -t "HCP Accounting (Weekly)"  -h 200 -w 600 -v "Used CPU's" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/month.png -a PNG -s -1month -t "HPC Accounting (Monthly)" -h 200 -w 600 -v "Used CPU's" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
rrdtool graph $WEB_ROOT/img/year.png  -a PNG -s -1year  -t "HPC Accounting (Yearly)"  -h 200 -w 600 -v "Used CPU's" COMMENT:'                    ' COMMENT:"Min"  COMMENT:" Max"  COMMENT:"  Avg" COMMENT:" Last\\l"   $datagrups   COMMENT:"Last update\: $DATE" 
