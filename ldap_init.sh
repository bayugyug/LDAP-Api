#!/bin/bash

###########################################################
# FUNCTIONS
###########################################################
function _init()
{
	
	LDAPROOT='!shrss!@#$%'
	LDAP_DN="dc=shrss,dc=domain"
	LDAP_PEOPLE="ou=People,${LDAP_DN}"
	LDAP_GROUPS="ou=Groups,${LDAP_DN}"
	ROOTD="/var/www/html/api"
	LOGDR="${ROOTD}/log"
	LOGF=${LOGDR}/ldap-delete-$(date '+%Y-%m-%d').log
	[[ ! -d "${LOGDR}" ]] && {
		mkdir -p ${LOGDR} 2>/dev/null
	}
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

