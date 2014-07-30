#!/usr/bin/perl

use strict;
use warnings;
use Data::Dumper;
use Carp;

my $QSTAT = '/netopt/uge/bin/lx-amd64/qstat';
my $QCONF = '/netopt/uge/bin/lx-amd64/qconf';
my $QQUOTA = '/netopt/uge/bin/lx-amd64/qquota';
my $config_file = './phpqstat.conf';  # simple key/value pairs

my $verbose = 1;

my @projects;

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
    my ($rrd_root, $g_r) = @_;
    my %files;

    foreach my $group (keys %{$g_r}) {
        print STDERR "Group=$group\n" if $verbose;
        my $file = "$rrd_root/qacct_prj_$group.rrd";
        if ( ! -f $file ) {
            create_rrd($group,$file);
        } else {
            #print STDERR "[$file] already exists.\n" ;
        }

        my $cmd = sprintf 'rrdtool update %s -t used:avail N:%d:%d', $file, @{$g_r->{$group}};
        print STDERR "update command: $cmd\n" if $verbose;
        system($cmd);
        if ($? == -1) { 
            croak "ERROR: $!";
        }

    }

    return %files;
}
#------------------------------------------g--------------
sub create_rrd {
    my ($group,$file) = @_;
    my $len = length $group < 12 ? length $group : 12;
    my $group_munged = substr($group,$len);
    $group_munged =~ s/-/_/g;
    my $DS = 'DS:used:GAUGE:300:0:999995000' . ' '
           . 'DS:avail:GAUGE:300:0:999995000';
    my $cmd = "rrdtool create $file -b -6y -s 180 $DS $RRAs";
    print STDERR "Running create_rrd command:  $cmd\n" if $verbose;
    #return system($cmd);
}
#---------------------------------------------------------
sub get_data {
    my ($qstat, @projects) = @_;
    my %counts;
    
    open (QSTAT, "$QSTAT -ext -u '*' |") or die "Failed to start qstat: $!";
    while (my $line = <QSTAT>) {
        next unless $line =~ /^\d/;
        my @line = split (' ', $line);
        my ($user, $project, $dept, $state) = @line[4..8];

        print "$user, $project, $dept, $state\n";

    }

    close QSTAT;
    return %counts;
}
#---------------------------------------------------------



parse_conf_file($config_file);  # This pollutes %ENV !!!!

my $rrd_root=make_rrd_root($ENV{RRD_ROOT});
@projects = get_projects if !@projects;

my %group= get_groups;
print Dumper(\%group) if $verbose;

insert_data($rrd_root,\%group);

