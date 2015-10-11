#!/bin/bash

###########################################################
# FUNCTIONS
###########################################################
function _init()
{
	ROOTD="/var/www/html/api"
	LOGDR="${ROOTD}/log"
	LDAPROOT='!shrss!@#$%'
	PASSDEF='abc123'
	LDAP_DN="dc=shrss,dc=domain"
	LDAP_PEOPLE="ou=People,${LDAP_DN}"
	LDAP_GROUPS="ou=Groups,${LDAP_DN}"
	LOGF=${LOGDR}/ldap-manual-$(date '+%Y-%m-%d').log
	TMFS=${LOGDR}/ldap-tmf-$(date '+%Y-%m-%d_%H%I%S')-$(printf "%05d-%05X-%05X" "$$" "$RANDOM" "$RANDOM").tmp
	TODAY=$(date '+%Y-%m-%d')
	MYSQL_BIN="mysql -urccl_api -prCcl@110415 prd_ldp_auth -h10.8.0.52 -P3306"
	UUID=$(printf "%04X-%04X" $$ ${RANDOM})
	#init it more
	[[ ! -d "${LOGDR}" ]] && {
		mkdir -p ${LOGDR} 2>/dev/null
	}
	
	ALLCNS="people,travel_mart,rclcrew,mstr,ctrac_applicant,ctrac_employee"
}

function timeStamp()
{
     local pid=$(printf "%05d" $$)
     echo "[$(date)] - info - $*" >> ${LOGF}

}

function trim()
{
	echo "$*" | sed -e '~s/^\s*//g' | sed -e '~s/\s*$//g'
}
###########################################################

#init
_init

