#!/bin/bash







#init
cd /var/www/html/api
.  ldap_init.sh


timeStamp "Start! [$@]"

USERID=$(trim  "$1")
COMPANY=$(trim "$2" | tr '[A-Z]' '[a-z]' 2>/dev/null)

timeStamp "USER-ID:${USERID} -> ${COMPANY}"

#sanity chk
[[ "0" == "${#USERID}" ]] && {
    
	echo "
	
	Oops, invalid parameters!
	
	$0 userid2delete <groups or people>
	
	"
	timeStamp "INVALID USER-ID:${USERID}"
	exit 1
}

[[ "0" == "${#COMPANY}" ]] && {
	#default
	COMPANY='people'
	timeStamp "Set Default CN:${COMPANY}"

}

[[ ! "${COMPANY}" =~ ^(people|groups)$ ]] && {
	timeStamp "INVALID CN:${COMPANY}"
	exit 1
}
#groupings
case "${COMPANY}" in
	groups)
	   grp=$LDAP_GROUPS
	;;
	*)
	   grp=$LDAP_GROUPS
	;;
	
esac


#run
/usr/bin/ldapdelete  -D "cn=Directory Manager" -x "uid=${USERID},ou=${COMPANY},dc=shrss,dc=domain"  -w "${LDAPROOT}"
dret=$?
timeStamp "del ${USERID} :${dret}"



timeStamp "Done!> $grp"
exit 0
