#!/bin/bash



function _sql()
{
cat <<-_EOF_

INSERT INTO stg_ldap_users (ouid,firstname,middlename,lastname,email,company_name)
VALUES('${USERIDX//\'/}','${GIVENNAME//\'/}','','${LASTNAME//\'/}','${EMAIL//\'/}','${COMPANY//\'/}');

_EOF_
}

#init
cd /var/www/html/api
.  ldap_init.sh


timeStamp "Start! [$@]"

LDIF_FN="${1}"

timeStamp "ldif:${LDIF_FN}"

#sanity chk
[[ ! -s "${LDIF_FN}" ]] && {
    
	echo "
	
	Oops, invalid parameters!
	
	$0 <LDIF FILE>
	
	"
	timeStamp "INVALID ldif:${LDIF_FN}"
	exit 1
}


[[ "dont" == "dohere" ]] && {
		echo "
		uid:travuser3
		givenName:travuser3
		dn: uid=travuser3,ou=Groups,dc=shrss,dc=domain
		objectClass: top
		objectClass: person
		objectClass: organizationalPerson
		objectClass: inetorgperson
		cn: travel_mart
		sn: travuser3
		mail:bayugs@gmail.com
		description:travuser3-desc here
		"
}

#parse
USERIDX=$(   trim "$(egrep '^uid:'         ${LDIF_FN} | cut -f2- -d: )" )
GIVENNAME=$( trim "$(egrep '^givenName:'   ${LDIF_FN} | cut -f2- -d: )" )
LASTNAME=$(  trim "$(egrep '^sn:'          ${LDIF_FN} | cut -f2- -d: )" )
EMAIL=$(     trim "$(egrep '^mail:'        ${LDIF_FN} | cut -f2- -d: )" )
DESC=$(      trim "$(egrep '^description:' ${LDIF_FN} | cut -f2- -d: )" )
COMPANY=$(   trim "$(egrep '^cn:'          ${LDIF_FN} | cut -f2- -d: )" )


timeStamp "p> ${USERIDX}"
timeStamp "p> ${GIVENNAME}"
timeStamp "p> ${LASTNAME}"
timeStamp "p> ${EMAIL}"
timeStamp "p> ${DESC}"
timeStamp "p> ${COMPANY}"

LDIF_TMF="${LOGDR}/${TODAY}.ldif-${UUID}.${COMPANY}.sql"

_sql > ${LDIF_TMF}

#show
echo "

		#run me below ;-)

		cat ${LDIF_TMF} | ${MYSQL_BIN}


"



timeStamp "Done!> $MYSQL_BIN"
exit 0
