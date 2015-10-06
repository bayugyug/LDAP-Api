#!/bin/bash







#init
cd /var/www/html/api
.  ldap_init.sh


timeStamp "Start! [$@]"

USERID=$(trim  "$1")

timeStamp "USER-ID:${USERID} -> ${COMPANY}"

#sanity chk
[[ "0" == "${#USERID}" ]] && {
    
	echo "
	
	Oops, invalid parameters!
	
	$0 userid2search
	
	"
	timeStamp "INVALID USER-ID:${USERID}"
	exit 1
}

#run
/usr/bin/ldapsearch -x -b "dc=shrss,dc=domain" '(&(uid=$USERID)(cn=*))' 
dret=$?
timeStamp "del ${USERID} :${dret}"



timeStamp "Done!> $grp"
exit 0
