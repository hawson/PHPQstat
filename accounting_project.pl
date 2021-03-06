#!/usr/bin/perl

use strict;
use warnings;
use Data::Dumper;
use Carp;

# Set these accordingly.
my $QSTAT = '/netopt/uge/bin/lx-amd64/qstat';
my $QCONF = '/netopt/uge/bin/lx-amd64/qconf';
my $QQUOTA = '/netopt/uge/bin/lx-amd64/qquota';
my $config_file = './phpqstat.conf';  # simple key/value pairs

my $verbose = 0;

# template fields are: department, state.
my $DS_tmpl = 'DS:prj-%s-%s:GAUGE:1000000:0:999995000 ';

my $RRAs = join(' ',(
    "RRA:AVERAGE:0.5:1:960", # 2 days, primary data points
    "RRA:AVERAGE:0.2:5:2976",    "RRA:MAX:0.2:5:2976",    "RRA:MIN:0.2:5:2976",    # 31 days, 15 min bins
    "RRA:AVERAGE:0.2:20:8784",   "RRA:MAX:0.2:20:8784",   "RRA:MIN:0.2:20:8784",   # 1 year, hourly bins
    "RRA:AVERAGE:0.1:480:1830",  "RRA:MAX:0.2:480:1830",  "RRA:MIN:0.2:480:1830",  # 5 years, daily bins
));


sub make_rrd_root {
    my $root=shift || return;
    my $rrdd="$root/rrd";
    if ( ! -d "$root/rrd") { mkdir $rrdd or die "Failed to make dir [$root]: $!" }
    return $rrdd;
}

#---------------------------------------------------------

sub parse_conf_file {
    my ($file) = @_;
    #my %config;
   
    $ENV{RRD_ROOT}='/home/beckerje/PHPQstat';

    open(my $conf,'<', $file) || croak "Failed opening conf file: $!";

    while (my $line = <$conf>) {
        next unless $line =~ /(SGE_\w+)=(.+)/;
        my ($var, $val) = ($1,$2);

        $val =~ s/^'"//;
        $val =~ s/'"$//;
        $ENV{$var} = $val;
    }

    close $conf;

    #%ENV= (%ENV, %config);  
    #return %config;
}
#---------------------------------------------------------
sub get_projects {
    my $cmd = "$QCONF -sprjl";
    my $output = `$cmd` or die "Failed to run [$cmd]: $!";
    return split("\n", $output);
}
#---------------------------------------------------------
sub insert_data {
    my ($rrd_root, $p_r) = @_;

    foreach my $project (keys %{$p_r}) {
        print STDERR "Prj=$project\n" if $verbose;
        my $file = "$rrd_root/qacct_prj_$project.rrd";
        if ( ! -f $file ) {
            print STDERR "Creating [$file].\n" if $verbose;
            create_rrd($project,$file);
        } else {
            print STDERR "[$file] already exists.\n" if $verbose;
        }

        my $cmd = sprintf 'rrdtool update %s -t running:pending N:%d:%d', $file, @{$p_r->{$project}};
        print STDERR "update command: $cmd\n" if $verbose;
        system($cmd);
        if ($? == -1) { 
            croak "ERROR: $!";
        }

    }

}
#------------------------------------------g--------------
sub create_rrd {
    my ($project,$file) = @_;
    my $len = length $project < 12 ? length $project : 12;
    my $project_munged = substr($project,$len);
    $project_munged =~ s/-/_/g;
    my $DS = 'DS:running:GAUGE:180:0:999995000' . ' '
           . 'DS:pending:GAUGE:180:0:999995000';
    my $cmd = "rrdtool create $file -b -6y -s 180 $DS $RRAs";
    print STDERR "Running create_rrd command:  $cmd\n" if $verbose;
    return system($cmd);
}
#---------------------------------------------------------
sub get_data {
    my ($qstat, @projects) = @_;
    my %counts;

    print STDERR "Getting data...\n" if $verbose;
    
# beckerje@systemsutils:~/PHPQstat (master)$ qstat -ext|head
# job-ID  prior   ntckts  name       user         project          department state cpu        mem     io      tckts ovrts otckt ftckt stckt share queue                          jclass                         slots ja-task-ID
# -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
# 9045318 1.49902 1.00000 efseg_2630 pmadm        pubmed           ieb        dr    NA         NA      NA      20452     0 20000   452     0 0.11  high@sge855                       1
# 1227476 0.50007 0.00007 TestQueryT wonkim       unified          cbb        r     0:00:13:52 37.20146 362.44018     1     0     0     1     0 0.00  unified@sge663                 1
# 1227477 0.50007 0.00007 TestQueryT wonkim       unified          cbb        r     0:00:18:59 50.50506 97.94086     1     0     0     1     0 0.00  unified@sge529                  1
# 3181928 0.50008 0.00008 sparse.369 agarwala     unified          ieb        qw                                   1     0     0     1     0 0.00                                                                    1
# 3181929 0.50008 0.00008 sparse.370 agarwala     unified          ieb        qw                                   1     0     0     1     0 0.00                                                                    1
# 3181930 0.50008 0.00008 sparse.371 agarwala     unified          ieb        qw                                   1     0     0     1     0 0.00                                                                    1


    open (my $QSTAT, '-|', "$QSTAT -ext -u '*'") or die "Failed to start qstat: $!";
    while (my $line = <$QSTAT>) {
        next unless $line =~ /^\d/;  
        my @line = split (' ', $line);
        my ($user, $project, $dept, $state) = @line[4..8];

        print "$user, $project, $dept, $state\n" if $verbose;

        my $idx = $state =~ /qw/ ? 1 : 0;
        $counts{$project}[$idx]++;
    }

    foreach my $prj (keys %counts) {
        foreach my $idx (0,1) {
            $counts{$prj}[$idx] ||= 0;
        }
    }

    close $QSTAT;
    return %counts;
}
#---------------------------------------------------------



parse_conf_file($config_file);  # This pollutes %ENV !!!!

my $rrd_root=make_rrd_root($ENV{RRD_ROOT});

my @projects= get_projects;
my %project = get_data(@projects);

print Dumper(\%project) if $verbose;

insert_data($rrd_root,\%project);

