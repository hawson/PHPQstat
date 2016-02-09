#!/usr/bin/perl

use strict;
use warnings;
use Data::Dumper;
use Carp;

# Set this accordingly...
my $QSTAT = '/netopt/uge/bin/lx-amd64/qstat';
my $QCONF = '/netopt/uge/bin/lx-amd64/qconf';
my $QQUOTA = '/netopt/uge/bin/lx-amd64/qquota';
my $config_file = './phpqstat.conf';  # simple key/value pairs

my $verbose = 0;

# template fields are: department, state.
my $DS_tmpl = 'DS:quota-%s-%s:GAUGE:1000000:0:999995000 ';

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
sub get_quotas {
    my $cmd = "$QQUOTA";
    my @output = `$cmd` or die "Failed to run [$cmd]: $!";
    my %quota;

    #resource quota rule limit                filter
    #--------------------------------------------------------------------------------
    #pnbrq_limit/1      slots=1000/1000      projects pnbrq
    #progressive_limit/1 slots=127/185        projects progressive

    foreach (@output) { 
        next unless /slots=/;
        chomp;
        my ($quota, $slots, $filter) = split(/\s+/,$_, 3);
        $quota =~ s,/\d+,,x;
        my ($used,$max) = $slots =~ m,slots=(\d+)/(\d+),x;

        if (defined($used) and defined($max)) {
            $quota{$quota} = [ $used,$max];
            print STDERR "[$quota] [$slots:$used,$max] [$filter]\n" if $verbose;
        } else {
            print STDERR "[$quota] Undefined value for used:max ($filter)\n" if $verbose;
        }

    }

    return %quota;
}
#---------------------------------------------------------
sub insert_data {
    my ($rrd_root, $q_r) = @_;
    my %files;

    foreach my $quota (keys %{$q_r}) {
        print STDERR "Quota=$quota\n" if $verbose;
        my $file = "$rrd_root/qacct_quota_$quota.rrd";
        if ( ! -f $file ) {
            create_rrd($quota,$file);
        } else {
            #print STDERR "[$file] already exists.\n" ;
        }

        my $cmd;
        $cmd = sprintf 'rrdtool update %s -t used:avail N:%d:%d', $file, @{$q_r->{$quota}} if grep { defined } @{$q_r->{$quota}};
        if (!$cmd) {
            print STDERR "Bad upate data for $quota.  File=$file\n" if $verbose;
        }
        print STDERR "update command: $cmd\n" if $verbose;
        system($cmd);
        if ($? == -1) { 
            croak "ERROR: $!";
        }

    }

    return %files;
}
#---------------------------------------------------------
sub create_rrd {
    my ($quota,$file) = @_;
    my $len = length $quota < 12 ? length $quota : 12;
    my $quota_munged = substr($quota,$len);
    $quota_munged =~ s/-/_/g;
    my $DS = 'DS:used:GAUGE:180:0:999995000' . ' '
           . 'DS:avail:GAUGE:180:0:999995000';
    my $cmd = "rrdtool create $file -b -6y -s 180 $DS $RRAs";
    print STDERR "Running create_rrd command:  $cmd\n" if $verbose;
    return system($cmd);
}
#---------------------------------------------------------



parse_conf_file($config_file);  # This pollutes %ENV !!!!

my $rrd_root=make_rrd_root($ENV{RRD_ROOT});

my %quota = get_quotas;
print Dumper(\%quota) if $verbose;

insert_data($rrd_root,\%quota);

