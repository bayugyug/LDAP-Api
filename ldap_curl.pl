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


use File::Basename;
use FindBin qw($Bin $Script $RealBin);
use Getopt::Std;
use Spreadsheet::ParseExcel;




#for buffering I/O
$| = 1;


my $URL      = "http://10.8.0.54/api/index.php/ldap/restapi/add";
my $PATH     = $Bin;
my $LDAPROOT = '!shrss!@#$%';
my $ROOT     = '/var/www/html/api';
my $LDIF     = 0;
my $PASSDEF  = 'abc123';
my $STATS    = {};

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
   exit 1
}
	



my $fl = basename($csv);
my @fn = split(/\./,$fl);

print "CSV FILE => ($csv) -> $fl\n";

my $cn = lc($fn[0]);
my $xt = $fn[1];

if($xt !~ /^(csv)$/i)
{
	print "
	
	Invalid extension found.
	
	Must be a CSV file!
	
	";
	exit 1;
}


print "info: $cn -> $xt\n";


if($cn !~ /^(travel_mart|rclcrew|mstr|ctrac_applicant|ctrac_employee)$/i)
{
	print "
	
	Invalid filenaming convention.
	
	Valid are:
	
	<travel_mart|rclcrew|mstr|ctrac_applicant|ctrac_employee>.csv
	
	";
	exit 1;
}

#"travelmart.dev","team","","travelmart","mwali@rccl.com"
open($fh, "< $csv") or die("ERROR: file open failed $@;\n");
my $parsed = 0;
while(<$fh>)
{
	chomp;
	
	~s/\://gi;
	
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
		print "Ignoring> blank uid $ldap_uid;\n";
		next;
	}
	if(length($ldap_name) <=0)
	{
		print "Ignoring> blank name $ldap_name;\n";
		next;
	}
	if(length($ldap_sn) <=0)
	{
		print "Ignoring> blank sn $ldap_sn;\n";
		next;
	}
	if(length($ldap_mail) <=0)
	{
		print "Ignoring> blank mail $ldap_mail;\n";
		next;
	}
	if(length($ldap_pass) <=0)
	{
		$ldap_pass = 'abc123';
		print "set default> blank password $ldap_pass;\n";
	}
	$parsed++;
	#chk
	&ldap_curl($cn,$ldap_uid,$ldap_name,$ldap_sn,$ldap_mail,$ldap_pass);
}

#free
close($fh);

#stats
while (my($k,$v) = each %$STATS)
{
	print "$k -> $v\n";
}














sub trim()
{
	my ($str) = (@_);
	$str =~ s/^\s*//g;
	$str =~ s/\s*$//g;
	return $str;
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
	my ($cn,$uid,$name,$sn,$mail,$pass) = (@_);
	
	my $m      = substr($name,0,1);
	my $cmd    = "/usr/bin/curl -X POST -d \"description=manual\" -d \"user=$uid\" -d \"pass=$pass\" -d \"company=$cn\" -d \"email=$mail\" -d \"firstname=$name\" -d \"lastname=$sn\" -d \"middname=$m\" \"$URL\" ";
	my $web    = ` $cmd 2>/dev/null`;
	chomp($web);
	if($web =~ /"status":true,/i)
	{
		print "DUMP-OK! [$uid/$mail]\n$web\n";
		$STATS->{"OK"}++;
	}
	else
	{
	   print "DUMP-NOT-OK! [$uid/$mail]\n$web\n";
	   $STATS->{"NOT-OK"}++;
	}
}
1;