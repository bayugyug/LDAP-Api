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
	
	#more
	$ldap_uid  =~ s/"//gi;
	$ldap_name =~ s/"//gi;
	$ldap_more =~ s/"//gi;
	$ldap_sn   =~ s/"//gi;
	$ldap_mail =~ s/"//gi;
	
	#clean
	$ldap_uid  = &trim($ldap_uid );
	$ldap_name = &trim($ldap_name);
	$ldap_more = &trim($ldap_more);
	$ldap_sn   = &trim($ldap_sn  );
	$ldap_mail = &trim($ldap_mail);	
	
	$parsed++;
	#chk
	&ldap_search($cn,$ldap_uid,$ldap_name,$ldap_sn,$ldap_mail);
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

sub ldap_search()
{
	
	my ($cn,$uid,$name,$sn,$mail) = (@_);
	
	my $cmd    = "/usr/bin/ldapsearch -x -b \"dc=shrss,dc=domain\" '(&(uid=$uid)(cn=*))' | egrep '^cn:' | cut -f2 -d: ";
	my $search = ` $cmd `;
	chomp($search);
	
	$LDIF++;
	
	my $line   = &trim($search);
	my $all    = [];
	my $ctr    = 0;
	my $found  = 0;
	my $cn_str = '';
	
	if(length($line)){
		my @cns = split(/\n/,$line);
		for(my $j=0; $j < $#cns+1; $j++)
		{
			my $cn1 = &trim($cns[$j]);
			if(length($cn1))
			{
			    $all[$ctr++] = "$cn1";
			}
		}
    
		#loop-all
		$cn_str = "cn: $cn\n";
		for(my $n=0; $n < $#all+1; $n++)
		{
			my $tmf = $all[$n];
			$found++;
			next if($cn =~ /^($tmf)$/);
			$cn_str .= "cn: $tmf\n";
		}
		$cn_str = &trim($cn_str);
	}
	
	
	my $dres= '';
	my $str = '';
	if($found>0)
	{
		
		#update
$str = "dn: uid=$uid,ou=Groups,dc=shrss,dc=domain
changetype: modify
replace: cn
$cn_str
";	
			#ldapmodify -x -D "cn=Directory Manager" -f upd.test.ldif
			my $cdir = sprintf("%s/csv",$ROOT);
			mkdir($cdir) if(! -d "$cdir");
			
			my $ldif = sprintf("%s/csv/upd.%07d-%04X.ldif",$ROOT,$$,$LDIF);
			
			#save
			&iosave($ldif, $str);
			
			$cmd = "/usr/bin/ldapmodify -x -D \"cn=Directory Manager\" -f $ldif -w '$LDAPROOT' ";
			
			
			$dres = ` $cmd 2>&1 `;
			chomp($dres);
			
			#modifying entry
			if($dres =~ /modifying entry/)
			{
				print "MODIFY ENTRY OK: $uid\n";
				$STATS->{"UPDATE-OK"}++;
			}
			else
			{
				print "MODIFY ENTRY NOT OK: $uid\n";
				$STATS->{"UPDATE-NOK"}++;
			}
			unlink($ldif) if(-e $ldif);
	}
	else
	{
		#add
		$str = "uid:$uid
givenName:$uid
dn: uid=$uid,ou=Groups,dc=shrss,dc=domain
objectClass: top
objectClass: person
objectClass: organizationalPerson
objectClass: inetorgperson
cn: $cn
sn: $sn
mail: $mail
description: manual new entry
";

			my $ldif = sprintf("%s/csv/add.%07d-%04X.ldif",$ROOT,$$,$LDIF);
			
			#save
			&iosave($ldif, $str);
			
			$cmd = "/usr/bin/ldapadd -x -D \"cn=Directory Manager\" -f $ldif -w '$LDAPROOT' ";
			
			#print
			
			
			#run
			$dres = ` $cmd 2>&1 `;
			chomp($dres);
			
			#modifying entry
			if($dres =~ /adding new entry/)
			{
				print "ADD ENTRY OK: $uid\n";
				$STATS->{"ADD-OK"}++;
			}
			else
			{
				print "ADD ENTRY NOT OK: $uid\n";
				$STATS->{"ADD-NOK"}++;
			}
				
			#add default password
			$cmd = "/usr/bin/ldappasswd -s $PASSDEF  -D \"cn=Directory Manager\" -x \"uid=$uid,ou=Groups,dc=shrss,dc=domain\" -w '$LDAPROOT' ";
			
			
			#run
			$dres = ` $cmd 2>&1 `;
			chomp($dres);
			$dres = &trim($dres);
			
			#passwdadd
			if(length($dres) <= 0)
			{
				print "DEF-PASSWORD ENTRY OK: $uid\n";
			}
			else
			{
				print "DEF-PASSWORD ENTRY NOT OK: $uid\n";
			}
			
			unlink($ldif) if(-e $ldif);
	
	}
	
}
1;