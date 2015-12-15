#!/usr/bin/perl
# @file    : ldap_loader.pl
# @desc    :
#            - csv load to ldap
#
# @version : 0.01
# @date    : 20151005
# @author  : bayugyug
# @modified:
#----------------------------------------------------------------------------------------


use utf8;
use File::Basename;
use FindBin qw($Bin $Script $RealBin);
use Getopt::Std;
use Spreadsheet::ParseExcel;
use POSIX;
use Digest::MD5;


#for buffering I/O
$| = 1;


my $URL      = "http://10.8.0.54/api/index.php/ldap/restapi/add";
my $PATH     = $Bin;
my $LDAPROOT = '!shrss!@#$%';
my $ROOT     = '/var/www/html/api';
my $LDIF     = 0;
my $PASSDEF  = 'abc123';
my $STATS    = {};
my $TODI     = strftime("%Y-%m-%d",localtime);
my $LOGFILE  = sprintf("%s/log/%s-ldap-curl.log",$ROOT,$TODI);

#get csv file
getopt("f:");


#get file
my $csv     = $opt_f;

#chk
unless(-e $csv){
 
print "

Oops, parameter invalid!

./$Script -f csv-file

";
exit 1;

}
	



`export LC_ALL=en_US.UTF-8 2>/dev/null`;
`export LANG=en_US.UTF-8 2>/dev/null`;
`export LANGUAGE=en_US.UTF-8 2>/dev/null`;

my $fl = basename($csv);
my @fn = split(/\./,$fl);
my $dst= sprintf("%s-%s-%d.done.csv",$csv,$TODI,$$);

print "

CSV FILE => ($csv) -> $fl

\n";

my $cn = lc($fn[0]);
my $dt = $fn[1];
my $xt = $fn[2];

if($xt !~ /^(csv)$/i)
{
	print "
	
	Invalid extension found.
	
	Must be a CSV file!
	
	<CN>.YYYYMMDD.csv
	
	";
	exit 1;
}

&debug("Start!!!");
&debug("Info: $cn -> $xt");


if($cn !~ /^(travel_mart|rclcrew|mstr|ctrac_applicant|ctrac_employee)$/i)
{
	&debug("
	
	Invalid filenaming convention.
	
	Valid are:
	
	<travel_mart|rclcrew|mstr|ctrac_applicant|ctrac_employee>.csv
	
	");
	exit 1;
}

#"travelmart.dev","team","","travelmart","mwali@rccl.com"
open($fh, "< $csv") or die("ERROR: file open failed $@;\n");
my $parsed = 0;
while(<$fh>)
{
	chomp;
	
	~s/\://gi;
	

	my $flag = utf8::is_utf8($_);
	my $enc  = utf8::encode($_);

        #my @rec = split(/,/,(($flag)?$enc:$_));
        my @rec = split(/,/);

	my $ldap_uid = $rec[0];
	my $ldap_name= $rec[1];
	my $ldap_more= $rec[2];
	my $ldap_sn  = $rec[3];
	my $ldap_mail= $rec[4];
	my $ldap_pass= $rec[5];
	
	#more
	$ldap_uid  =~ s/"//gi;
	$ldap_name =~ s/"//gi;
	$ldap_more =~ s/"//gi;
	$ldap_sn   =~ s/"//gi;
	$ldap_mail =~ s/"//gi;
	$ldap_pass =~ s/"//gi;
	
	#clean
	$ldap_uid  = &trim($ldap_uid );
	$ldap_name = &trim($ldap_name);
	$ldap_more = &trim($ldap_more);
	$ldap_sn   = &trim($ldap_sn  );
	$ldap_mail = &trim($ldap_mail);	
	$ldap_pass = &trim($ldap_pass);
	
	if(length($ldap_uid) <=0)
	{
		&debug("Ignoring> blank uid $ldap_uid;");
		next;
	}
	if(length($ldap_name) <=0)
	{
		&debug("Warn> blank name $ldap_name;");
		$ldap_name = sprintf("blank-name-%08d",$parsed);
	}
	if(length($ldap_sn) <=0)
	{
		&debug("Warn> blank sn $ldap_sn;");
		$ldap_sn   = sprintf("blank-sn-%08d",$parsed);
	}
	if(length($ldap_mail) <=0)
	{
		&debug("Ignoring> blank mail $ldap_mail;");
		next;
	}
	if(length($ldap_pass) <=0)
	{
	    my $rstr      = Digest::MD5::md5_base64( rand );
		   $rstr      =~ s/[^A-Z0-9]+//gi;
		   $ldap_pass = substr($rstr,0,8);
		&debug("set default> blank password=> '$ldap_pass'");
	}
	$parsed++;
	#chk
	&ldap_curl($parsed,$cn,$ldap_uid,$ldap_name,$ldap_sn,$ldap_mail,$ldap_pass);
}

#free
close($fh);

#stats
while (my($k,$v) = each %$STATS)
{
	&debug("STATS> $k -> $v");
}
&debug("Done");




sub trim()
{
	my ($str) = (@_);
	$str =~ s/^\s*//g;
	$str =~ s/\s*$//g;
	return $str;
}

sub debug()
{
	my ($msg) = (@_);
	my $ts    = strftime("%Y-%m-%d %H:%M:%S",localtime);
	
	print "[$ts] - $msg\n";
	
	open($wh,">>$LOGFILE") or die("ERROR: file open failed $!\n");
	print $wh "[$ts] - $msg\n";
	close($wh);
}

sub iosave()
{
	my($fn,$msg) = (@_);
	open($wh,">$fn") or die("ERROR: file open failed $!\n");
	print $wh "$msg";
	close($wh);
}

sub ldap_curl()
{
	my ($parsed,$cn,$uid,$name,$sn,$mail,$pass) = (@_);
	
	my $m      = substr($name,0,1);
	my $cmd    = "/usr/bin/curl -X POST -d \"description=manual\" -d \"user=$uid\" -d \"pass=$pass\" -d \"company=$cn\" -d \"email=$mail\" -d \"firstname=$name\" -d \"lastname=$sn\" -d \"middname=$m\" \"$URL\" ";
	my $web    = ` $cmd 2>/dev/null`;
	chomp($web);

		&debug("$parsed; #CMD! - $cmd;");
	if($web =~ /"status":true,/i)
	{
		&debug("$parsed; #DUMP-OK! [$uid/$mail] - $web");
		$STATS->{"OK"}++;
	}
	else
	{
	   &debug("$parsed; #DUMP-NOT-OK! [$uid/$mail] -> $web");
	   $STATS->{"NOT-OK"}++;
	}
}
1;
