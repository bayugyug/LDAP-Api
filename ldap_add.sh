#!/bin/bash


#init
cd /var/www/html/api
.  ldap_init.sh


#############
function usage()
{
	echo "
	
	Oops, invalid parameters!
	
	$0 <USERID> <FULLNAME> <CN> <EMAIL> <PASSWORD[optional]>
	
	"

	timeStamp "INVALID parameters!"
	exit 1
}

function ldif_add()
{
cat <<-_EOF_
uid:${USERID}
givenName:${FNAME}
dn: uid=${USERID},ou=Groups,dc=shrss,dc=domain
objectClass: top
objectClass: person
objectClass: organizationalPerson
objectClass: inetorgperson
cn: ${COMPANY}
sn: ${FNAME}
mail: ${EMAIL}
description: by manual new entry
_EOF_
}


function ldif_modify()
{
    local fn="${1}"
	
cat <<-_EOF_
dn: uid=${USERID},ou=Groups,dc=shrss,dc=domain
changetype: modify
replace: cn
$(cat $fn 2>/dev/null)
description: by manual new entry $(date '+%Y-%m-%d %H%I%S')
_EOF_
}


#############





timeStamp "Start! [$@]"

USERID=$(  trim   "${1}")
FNAME=$(   trim   "${2}")
COMPANY=$( trim   "${3}")
EMAIL=$(   trim   "${4}")
MPASSWD=$( trim   "${5:-abc123}")

timeStamp "params>${USERID};"
timeStamp "params>${FNAME};"
timeStamp "params>${COMPANY};"
timeStamp "params>${EMAIL};"
timeStamp "params>${MPASSWD};"

#sanity chk
[[ "0" == "${#USERID}" ]] && {
	timeStamp "Invalid params>! ${USERID}"
	usage
}

#sanity chk
[[ "0" == "${#FNAME}" ]] && {
	timeStamp "Invalid params>! ${FNAME}"
	usage
}

#sanity chk
[[ "0" == "${#COMPANY}" || ! ${COMPANY} =~ ^(people|travel_mart|rclcrew|mstr|ctrac_applicant|ctrac_employee)$ ]] && {
	timeStamp "Invalid params>! ${COMPANY}"
	usage
}

#sanity chk
[[ "0" == "${#EMAIL}" ]] && {
	timeStamp "Invalid params>! ${EMAIL}"
	usage
}

#sanity chk
[[ "0" == "${#MPASSWD}" ]] && {
	timeStamp "Invalid params>! default -> ${MPASSWD}"
	MPASSWD="${PASSDEF}"
}


TMP_CNF="${TMFS}.x"
>${TMP_CNF}

UPDATEMODE=0
ISFOUND=0
#run
eval `/usr/bin/ldapsearch -x -b "dc=shrss,dc=domain" "(&(uid=$USERID)(cn=*))"  | \
egrep '^cn:'  | cut -f2 -d: | sed -e '~s/^\s*//g' | sed -e '~s/\s*$//g' | \
awk --re-interval \
-vUSERID="${USERID}"   \
-vCOMPANY="${COMPANY}" \
-vFNAME="${FNAME}"     \
-vEMAIL="${EMAIL}"     \
-vMPASSWD="${MPASSWD}" \
-vTMP_CNF="${TMP_CNF}" \
-vALLCNS="${ALLCNS}"   \
-vLOGF="${LOGF}"   \
		   'BEGIN{
				FS = ":"
				
				#vars
				isfound = 0;
				isupdate= 0;
				
				#init
				printf("cn: %s\n",COMPANY) >> TMP_CNF;
		   }
		   function timestamp(s)
		   {
		      print sprintf("[ %s ] - %s",strftime("%Y-%m-%d %H:%I:%S"), s ) >> LOGF
		   }
		   
		   /^[0-9a-zA-z]+/{
				 timestamp("CN> " $0);
				 
				 if($1 == COMPANY)
				 {
				    isfound++;
					timestamp("raw> SAME-CN: " $0);
					next;
				 }
				 isupdate++;
				 printf("cn: %s\n",$1) >> TMP_CNF;
				 next
		   }
		   END{
			   printf("UPDATEMODE=\"%d\"\n",isupdate);
			   printf("ISFOUND=\"%d\"\n",isfound);
		   }' 2>>${LOGF} `
		   
dret=$?
timeStamp "UPDATEMODE for ${USERID} #${dret} -> ${UPDATEMODE}"

#save it
TMP_LDIF="${TMFS}.x.ldif"
	
	

if [[ "0" == "${UPDATEMODE}" && "0" == "${ISFOUND}" ]]
then
    
	#ADD
	ldif_add > ${TMP_LDIF}
	ADD_REF=$( /usr/bin/ldapadd -x -D "cn=Directory Manager" -f ${TMP_LDIF} -w "${LDAPROOT}" )
	addret=$?
	
	
	#chk
	retok=$( echo ${ADD_REF} | egrep 'adding new entry' )
	if [[ "${#retok}" -gt 0 ]]
	then
	    smsg="LDAP ADD SUCCESS!"
	else
		smsg="LDAP ADD FAILED!"
	fi
	timeStamp "ADD> ${USERID} > ${smsg}"
	timeStamp "LDIF> $(cat ${TMP_LDIF:-xxxx} 2>/dev/null)"
	
	[[ "0" == "${addret}" ]] && {
		PWD_REF=$( /usr/bin/ldappasswd -s ${MPASSWD}  -D "cn=Directory Manager" -x "uid=${USERID},ou=Groups,dc=shrss,dc=domain" -w "${LDAPROOT}" )
		if [[ "${#PWD_REF}" -eq 0 ]]
		then
			smsg="LDAP PASSWD SUCCESS!"
		else
			smsg="LDAP PASSWD FAILED!"
		fi
		timeStamp "PASSWORD> ${USERID} > ${smsg}"
	}
else
    #chk
	IS_SAME=$(cat ${TMP_CNF} 2>/dev/null | egrep -v "cn: $COMPANY" | wc -l | cut -f1 -d" ")
	if [[ "${IS_SAME:-0}" -eq 0 ]]
	then
			smsg="LDAP UPDATE ignored!"
			timeStamp "UPDATE> ${USERID} > ${smsg}"
	else	
	
			#UPDATE
			ldif_modify ${TMP_CNF} > ${TMP_LDIF}
			UPD_REF=$( /usr/bin/ldapmodify -x -D "cn=Directory Manager" -f ${TMP_LDIF} -w "${LDAPROOT}" )
			
			timeStamp "LDIF> $(cat ${TMP_LDIF:-xxxx} 2>/dev/null)"
			
			#chk
			retok=$( echo ${UPD_REF} | egrep 'modifying entry' )
			if [[ "${#retok}" -gt 0 ]]
			then
				smsg="LDAP UPDATE SUCCESS!"
			else
				smsg="LDAP UPDATE FAILED!"
			fi
			timeStamp "UPDATE> ${USERID} > ${smsg}"
	fi
fi


#free
[[ -e "${TMP_CNF}" ]]  && rm -f ${TMP_CNF:-xxxxx}  2>/dev/null
[[ -e "${TMP_LDIF}" ]] && rm -f ${TMP_LDIF:-xxxxx} 2>/dev/null



timeStamp "Done!> $grp"
exit 0
